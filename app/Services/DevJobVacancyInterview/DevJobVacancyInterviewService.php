<?php

namespace App\Services\DevJobVacancyInterview;

use App\Enums\DevJobVacancyInterviewStatusEnum;
use App\Events\InterviewSignalReceived;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevJobVacancyInterview\DevJobVacancyInterviewResource;
use App\Models\DevJobVacancyInterview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevJobVacancyInterviewService {

    /**
     * Relações carregadas em toda entrevista retornada, necessárias para montar o
     * resource (perfis do dev e da empresa) e as notificações
     */
    private const RELATIONS = ['jobVacancy.companyProfile', 'devJobVacancy.devProfile'];

    /**
     * Janela de tolerância, em minutos, para entrar na call: liberado a partir de
     * X minutos antes do horário marcado, até Y minutos depois do fim previsto
     */
    private const JOIN_WINDOW_BEFORE_MINUTES = 10;
    private const JOIN_WINDOW_GRACE_AFTER_MINUTES = 15;

    /**
     * Empresa define o horário inicial da entrevista, ainda sem nenhuma proposta.
     * A entrevista passa a aguardar a confirmação do dev
     */
    public function setInitialSchedule(array $data): DevJobVacancyInterviewResource {

        $interview = $this->getCompanyInterview($data['id']);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::AWAITING_SCHEDULE) {
            throw new ApiException("This interview already has a schedule proposal!");
        }

        return $this->proposeToDev($interview, $data);

    }

    /**
     * Empresa responde a contraproposta do dev com um novo horário. A entrevista
     * volta a aguardar a confirmação do dev
     */
    public function companyProposeSchedule(array $data): DevJobVacancyInterviewResource {

        $interview = $this->getCompanyInterview($data['id']);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::AWAITING_COMPANY_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal from the developer to respond to!");
        }

        return $this->proposeToDev($interview, $data);

    }

    /**
     * Dev propõe um novo horário em resposta à proposta da empresa. A entrevista
     * passa a aguardar a confirmação da empresa
     */
    public function devProposeSchedule(array $data): DevJobVacancyInterviewResource {

        $interview = $this->getDevInterview($data['id']);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::AWAITING_DEV_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal from the company to respond to!");
        }

        return DB::transaction(function() use ($interview, $data) {

            $interview->update([
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'duration_in_minutes' => $data['duration_in_minutes'],
                'status' => DevJobVacancyInterviewStatusEnum::AWAITING_COMPANY_CONFIRMATION
            ]);

            $this->notifyCompany(
                $interview,
                'interview_schedule_proposed',
                'Novo horário de entrevista proposto',
                "O desenvolvedor {$interview->devJobVacancy?->devProfile?->name} propôs um novo horário para a entrevista da vaga {$interview->jobVacancy?->title}: {$interview->scheduled_at->format('d/m/Y H:i')}. Acesse a plataforma para confirmar ou propor outro horário."
            );

            return new DevJobVacancyInterviewResource($interview);

        });

    }

    /**
     * Dev aceita o horário proposto pela empresa. A entrevista fica aprovada
     */
    public function devAcceptSchedule(array $data): DevJobVacancyInterviewResource {

        $interview = $this->getDevInterview($data['id']);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::AWAITING_DEV_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal to accept!");
        }

        return $this->approve($interview, notifyCompany: true);

    }

    /**
     * Empresa aceita o horário proposto pelo dev. A entrevista fica aprovada
     */
    public function companyAcceptSchedule(array $data): DevJobVacancyInterviewResource {

        $interview = $this->getCompanyInterview($data['id']);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::AWAITING_COMPANY_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal to accept!");
        }

        return $this->approve($interview, notifyCompany: false);

    }

    /**
     * Cancela a entrevista, seja qual for a etapa da negociação em que ela esteja
     * (inclusive já aprovada), e avisa a outra parte. Exige role `dev` ou `company`
     * e que a entrevista pertença ao usuário autenticado
     */
    public function cancel(array $data): DevJobVacancyInterviewResource {

        $authUser = Auth::user();

        $isCompany = $authUser->hasRole('company');

        $interview = $isCompany
            ? $this->getCompanyInterview($data['id'])
            : $this->getDevInterview($data['id']);

        if(in_array($interview->status, [
            DevJobVacancyInterviewStatusEnum::CANCELLED,
            DevJobVacancyInterviewStatusEnum::REJECTED
        ], true)) {
            throw new ApiException("This interview is already cancelled or rejected!");
        }

        return DB::transaction(function() use ($interview, $isCompany) {

            $interview->update(['status' => DevJobVacancyInterviewStatusEnum::CANCELLED]);

            $jobTitle = $interview->jobVacancy?->title;

            if($isCompany) {
                $this->notifyDev(
                    $interview,
                    'interview_schedule_cancelled',
                    'Entrevista cancelada',
                    "A empresa {$interview->jobVacancy?->companyProfile?->name} cancelou a entrevista da vaga {$jobTitle}."
                );
            } else {
                $this->notifyCompany(
                    $interview,
                    'interview_schedule_cancelled',
                    'Entrevista cancelada',
                    "O desenvolvedor {$interview->devJobVacancy?->devProfile?->name} cancelou a entrevista da vaga {$jobTitle}."
                );
            }

            return new DevJobVacancyInterviewResource($interview);

        });

    }

    /**
     * Entra na call da entrevista: valida que o horário foi confirmado e que já
     * estamos dentro da janela de tolerância, marca `started_at` na primeira
     * entrada e devolve o que o front precisa para montar o RTCPeerConnection
     * (canal privado de sinalização e os ICE servers).
     *
     * NOTA: por enquanto só STUN público é devolvido. Quando tivermos um servidor
     * TURN, as credenciais (de curta duração) entram aqui, em `iceServers()`.
     *
     * @return array{
     *     channel: string,
     *     role: string,
     *     ice_servers: array,
     *     interview: DevJobVacancyInterviewResource
     * }
     */
    public function join(array $data): array {

        $authUser = Auth::user();
        [$interview, $role] = $this->getParticipantInterview($data['id'], $authUser);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::APPROVED) {
            throw new ApiException("This interview does not have a confirmed schedule yet!");
        }

        $this->assertWithinJoinWindow($interview);

        if(!$interview->started_at) {
            $interview->update(['started_at' => now()]);
        }

        return [
            'channel' => "interview.{$interview->id}",
            'role' => $role,
            'ice_servers' => $this->iceServers(),
            'interview' => new DevJobVacancyInterviewResource($interview)
        ];

    }

    /**
     * Sai da call da entrevista. Como é uma call 1:1, a saída de qualquer um dos
     * lados encerra a call — marca `ended_at` na primeira saída
     */
    public function leave(array $data): DevJobVacancyInterviewResource {

        $authUser = Auth::user();
        [$interview] = $this->getParticipantInterview($data['id'], $authUser);

        if(!$interview->ended_at) {
            $interview->update(['ended_at' => now()]);
        }

        return new DevJobVacancyInterviewResource($interview);

    }

    /**
     * Repassa um sinal WebRTC (offer, answer ou ICE candidate) para o outro
     * participante da entrevista, via o canal privado da entrevista. O backend
     * nunca vê o conteúdo da call em si, só essa etapa de sinalização
     */
    public function signal(array $data): void {

        $authUser = Auth::user();
        [$interview, $role] = $this->getParticipantInterview($data['id'], $authUser);

        if($interview->status !== DevJobVacancyInterviewStatusEnum::APPROVED) {
            throw new ApiException("This interview does not have a confirmed schedule yet!");
        }

        InterviewSignalReceived::dispatch($interview->id, $role, $data['type'], $data['payload']);

    }

    /**
     * Registra o horário proposto pela empresa e avisa o dev, deixando a
     * entrevista aguardando a confirmação dele
     */
    private function proposeToDev(DevJobVacancyInterview $interview, array $data): DevJobVacancyInterviewResource {

        return DB::transaction(function() use ($interview, $data) {

            $interview->update([
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'duration_in_minutes' => $data['duration_in_minutes'],
                'status' => DevJobVacancyInterviewStatusEnum::AWAITING_DEV_CONFIRMATION
            ]);

            $this->notifyDev(
                $interview,
                'interview_schedule_proposed',
                'Novo horário de entrevista proposto',
                "A empresa {$interview->jobVacancy?->companyProfile?->name} propôs um horário para a sua entrevista da vaga {$interview->jobVacancy?->title}: {$interview->scheduled_at->format('d/m/Y H:i')}. Acesse a plataforma para confirmar ou propor outro horário."
            );

            return new DevJobVacancyInterviewResource($interview);

        });

    }

    /**
     * Aprova o horário vigente da entrevista e avisa a outra parte
     */
    private function approve(DevJobVacancyInterview $interview, bool $notifyCompany): DevJobVacancyInterviewResource {

        return DB::transaction(function() use ($interview, $notifyCompany) {

            $interview->update(['status' => DevJobVacancyInterviewStatusEnum::APPROVED]);

            $scheduledAt = $interview->scheduled_at->format('d/m/Y H:i');
            $jobTitle = $interview->jobVacancy?->title;

            if($notifyCompany) {
                $this->notifyCompany(
                    $interview,
                    'interview_schedule_approved',
                    'Horário de entrevista confirmado',
                    "O desenvolvedor {$interview->devJobVacancy?->devProfile?->name} confirmou o horário da entrevista da vaga {$jobTitle}: {$scheduledAt}."
                );
            } else {
                $this->notifyDev(
                    $interview,
                    'interview_schedule_approved',
                    'Horário de entrevista confirmado',
                    "A empresa {$interview->jobVacancy?->companyProfile?->name} confirmou o horário da entrevista da vaga {$jobTitle}: {$scheduledAt}."
                );
            }

            return new DevJobVacancyInterviewResource($interview);

        });

    }

    private function notifyDev(DevJobVacancyInterview $interview, string $type, string $title, string $message): void {

        $interview->devJobVacancy?->devProfile?->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => env('APP_URL') . "/job-vacancy/{$interview->job_vacancy_id}"
        ]);

    }

    private function notifyCompany(DevJobVacancyInterview $interview, string $type, string $title, string $message): void {

        $interview->jobVacancy?->companyProfile?->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => env('APP_URL') . "/job-vacancy/{$interview->job_vacancy_id}"
        ]);

    }

    /**
     * Garante que a entrevista pertence a uma vaga do perfil de empresa autenticado
     */
    private function getCompanyInterview(string $id): DevJobVacancyInterview {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't manage this interview!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $interview = DevJobVacancyInterview::query()->with(self::RELATIONS)
            ->where('id', $id)
            ->whereHas('jobVacancy', fn($query) => $query->where('company_profile_id', $companyProfile->id))
            ->first();

        if(!$interview) {
            throw new ApiException("This interview does not belong to your company!", 403);
        }

        return $interview;

    }

    /**
     * Garante que a entrevista pertence à candidatura do perfil de dev autenticado
     */
    private function getDevInterview(string $id): DevJobVacancyInterview {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't manage this interview!");
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $interview = DevJobVacancyInterview::query()->with(self::RELATIONS)
            ->where('id', $id)
            ->whereHas('devJobVacancy', fn($query) => $query->where('dev_profile_id', $devProfile->id))
            ->first();

        if(!$interview) {
            throw new ApiException("This interview does not belong to you!", 403);
        }

        return $interview;

    }

    /**
     * Garante que a entrevista pertence ao usuário autenticado, seja como dev ou
     * como empresa, e devolve qual dos dois papéis ele ocupa nela
     *
     * @return array{0: DevJobVacancyInterview, 1: string}
     */
    private function getParticipantInterview(string $id, User $authUser): array {

        $interview = DevJobVacancyInterview::query()->with(self::RELATIONS)->find($id);

        if(!$interview) {
            throw new ApiException("Interview not found!", 404);
        }

        if($authUser->dev_profile?->id === $interview->devJobVacancy?->dev_profile_id) {
            return [$interview, 'dev'];
        }

        if($authUser->company_profile?->id === $interview->jobVacancy?->company_profile_id) {
            return [$interview, 'company'];
        }

        throw new ApiException("This interview does not belong to you!", 403);

    }

    /**
     * Só libera a entrada na call dentro de uma janela em torno do horário
     * marcado, evitando entrar (ou ficar preso no canal) fora de hora
     */
    private function assertWithinJoinWindow(DevJobVacancyInterview $interview): void {

        if(!$interview->scheduled_at) {
            throw new ApiException("This interview does not have a scheduled time yet!");
        }

        $opensAt = $interview->scheduled_at->copy()->subMinutes(self::JOIN_WINDOW_BEFORE_MINUTES);
        $closesAt = $interview->scheduled_at->copy()
            ->addMinutes($interview->duration_in_minutes ?? 0)
            ->addMinutes(self::JOIN_WINDOW_GRACE_AFTER_MINUTES);

        if(now()->lt($opensAt) || now()->gt($closesAt)) {
            throw new ApiException("It's not time for this interview yet, or the join window has already closed!");
        }

    }

    /**
     * Servidores ICE devolvidos para o front montar o RTCPeerConnection.
     *
     * TODO: hoje só STUN público (Google), suficiente para conexões diretas.
     * Quando tivermos um servidor TURN (necessário para redes com NAT
     * simétrico/firewall restritivo, comum em rede corporativa), gerar aqui
     * credenciais de curta duração e adicioná-las a essa lista
     */
    private function iceServers(): array {

        return [
            ['urls' => ['stun:stun.l.google.com:19302']]
        ];

    }

}
