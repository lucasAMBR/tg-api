<?php

namespace App\Services\DevJobVacancyLiveCoding;

use App\Enums\DevJobVacancyLiveCodingStatusEnum;
use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Events\LiveCodingSignalReceived;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevJobVacancyLiveCoding\DevJobVacancyLiveCodingResource;
use App\Models\DevJobVacancy;
use App\Models\DevJobVacancyLiveCoding;
use App\Models\JobVacancy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevJobVacancyLiveCodingService {

    /**
     * Relações carregadas em todo live coding retornado, necessárias para montar o
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
     * Empresa publica (ou corrige) o enunciado do exercício que o dev vai resolver
     * na call. Pode ser feito a qualquer momento antes do fim do live coding — o
     * combinado com o candidato é o horário, não o enunciado.
     *
     * O dev só é avisado na primeira publicação; as correções seguintes são silenciosas
     */
    public function setExercise(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getCompanyLiveCoding($data['id']);

        $this->assertNotFinished($liveCoding);

        $isFirstPublication = !$liveCoding->hasStatement();

        return DB::transaction(function() use ($liveCoding, $data, $isFirstPublication) {

            // Só altera o que foi enviado: uma correção só do enunciado não apaga a
            // linguagem já definida, e mandar `null` nela continua limpando
            $liveCoding->update(Arr::only($data, ['statement', 'programming_language']));

            if($isFirstPublication) {
                $this->notifyDev(
                    $liveCoding,
                    'live_coding_exercise_published',
                    'Exercício do live coding disponível',
                    "A empresa {$liveCoding->jobVacancy?->companyProfile?->name} publicou o exercício do live coding da vaga {$liveCoding->jobVacancy?->title}. Acesse a plataforma para ler o enunciado antes da call."
                );
            }

            // A vaga pode ter avançado para a etapa antes dos exercícios existirem. Nesse
            // caso as candidaturas estão paradas aguardando os enunciados e começam agora,
            // assim que o último deles for publicado
            if($liveCoding->jobVacancy) {
                $this->startAwaitingLiveCodingStep($liveCoding->jobVacancy);
            }

            return new DevJobVacancyLiveCodingResource($liveCoding);

        });

    }

    /**
     * Empresa define o horário inicial do live coding, ainda sem nenhuma proposta.
     * O live coding passa a aguardar a confirmação do dev
     */
    public function setInitialSchedule(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getCompanyLiveCoding($data['id']);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::AWAITING_SCHEDULE) {
            throw new ApiException("This live coding already has a schedule proposal!");
        }

        return $this->proposeToDev($liveCoding, $data);

    }

    /**
     * Empresa responde a contraproposta do dev com um novo horário. O live coding
     * volta a aguardar a confirmação do dev
     */
    public function companyProposeSchedule(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getCompanyLiveCoding($data['id']);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::AWAITING_COMPANY_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal from the developer to respond to!");
        }

        return $this->proposeToDev($liveCoding, $data);

    }

    /**
     * Dev propõe um novo horário em resposta à proposta da empresa. O live coding
     * passa a aguardar a confirmação da empresa
     */
    public function devProposeSchedule(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getDevLiveCoding($data['id']);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::AWAITING_DEV_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal from the company to respond to!");
        }

        return DB::transaction(function() use ($liveCoding, $data) {

            $liveCoding->update([
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'duration_in_minutes' => $data['duration_in_minutes'],
                'status' => DevJobVacancyLiveCodingStatusEnum::AWAITING_COMPANY_CONFIRMATION
            ]);

            $this->notifyCompany(
                $liveCoding,
                'live_coding_schedule_proposed',
                'Novo horário de live coding proposto',
                "O desenvolvedor {$liveCoding->devJobVacancy?->devProfile?->name} propôs um novo horário para o live coding da vaga {$liveCoding->jobVacancy?->title}: {$liveCoding->scheduled_at->format('d/m/Y H:i')}. Acesse a plataforma para confirmar ou propor outro horário."
            );

            return new DevJobVacancyLiveCodingResource($liveCoding);

        });

    }

    /**
     * Dev aceita o horário proposto pela empresa. O live coding fica aprovado
     */
    public function devAcceptSchedule(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getDevLiveCoding($data['id']);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::AWAITING_DEV_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal to accept!");
        }

        return $this->approve($liveCoding, notifyCompany: true);

    }

    /**
     * Empresa aceita o horário proposto pelo dev. O live coding fica aprovado
     */
    public function companyAcceptSchedule(array $data): DevJobVacancyLiveCodingResource {

        $liveCoding = $this->getCompanyLiveCoding($data['id']);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::AWAITING_COMPANY_CONFIRMATION) {
            throw new ApiException("There is no pending schedule proposal to accept!");
        }

        return $this->approve($liveCoding, notifyCompany: false);

    }

    /**
     * Cancela o live coding, seja qual for a etapa da negociação em que ele esteja
     * (inclusive já aprovado), e avisa a outra parte. Exige role `dev` ou `company`
     * e que o live coding pertença ao usuário autenticado
     */
    public function cancel(array $data): DevJobVacancyLiveCodingResource {

        $authUser = Auth::user();

        $isCompany = $authUser->hasRole('company');

        $liveCoding = $isCompany
            ? $this->getCompanyLiveCoding($data['id'])
            : $this->getDevLiveCoding($data['id']);

        if(in_array($liveCoding->status, [
            DevJobVacancyLiveCodingStatusEnum::CANCELLED,
            DevJobVacancyLiveCodingStatusEnum::REJECTED
        ], true)) {
            throw new ApiException("This live coding is already cancelled or rejected!");
        }

        return DB::transaction(function() use ($liveCoding, $isCompany) {

            $liveCoding->update(['status' => DevJobVacancyLiveCodingStatusEnum::CANCELLED]);

            $jobTitle = $liveCoding->jobVacancy?->title;

            if($isCompany) {
                $this->notifyDev(
                    $liveCoding,
                    'live_coding_schedule_cancelled',
                    'Live coding cancelado',
                    "A empresa {$liveCoding->jobVacancy?->companyProfile?->name} cancelou o live coding da vaga {$jobTitle}."
                );
            } else {
                $this->notifyCompany(
                    $liveCoding,
                    'live_coding_schedule_cancelled',
                    'Live coding cancelado',
                    "O desenvolvedor {$liveCoding->devJobVacancy?->devProfile?->name} cancelou o live coding da vaga {$jobTitle}."
                );
            }

            return new DevJobVacancyLiveCodingResource($liveCoding);

        });

    }

    /**
     * Entra na call do live coding: valida que o horário foi confirmado e que já
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
     *     live_coding: DevJobVacancyLiveCodingResource
     * }
     */
    public function join(array $data): array {

        $authUser = Auth::user();
        [$liveCoding, $role] = $this->getParticipantLiveCoding($data['id'], $authUser);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::APPROVED) {
            throw new ApiException("This live coding does not have a confirmed schedule yet!");
        }

        $this->assertWithinJoinWindow($liveCoding);

        if(!$liveCoding->started_at) {
            $liveCoding->update(['started_at' => now()]);
        }

        return [
            'channel' => "live-coding.{$liveCoding->id}",
            'role' => $role,
            'ice_servers' => $this->iceServers(),
            'live_coding' => new DevJobVacancyLiveCodingResource($liveCoding)
        ];

    }

    /**
     * Sai da call do live coding. Como é uma call 1:1, a saída de qualquer um dos
     * lados encerra a call — marca `ended_at` na primeira saída
     */
    public function leave(array $data): DevJobVacancyLiveCodingResource {

        $authUser = Auth::user();
        [$liveCoding] = $this->getParticipantLiveCoding($data['id'], $authUser);

        if(!$liveCoding->ended_at) {
            $liveCoding->update(['ended_at' => now()]);
        }

        return new DevJobVacancyLiveCodingResource($liveCoding);

    }

    /**
     * Repassa um sinal WebRTC (offer, answer ou ICE candidate) para o outro
     * participante do live coding, via o canal privado dele. O backend nunca vê o
     * conteúdo da call em si, só essa etapa de sinalização
     */
    public function signal(array $data): void {

        $authUser = Auth::user();
        [$liveCoding, $role] = $this->getParticipantLiveCoding($data['id'], $authUser);

        if($liveCoding->status !== DevJobVacancyLiveCodingStatusEnum::APPROVED) {
            throw new ApiException("This live coding does not have a confirmed schedule yet!");
        }

        LiveCodingSignalReceived::dispatch($liveCoding->id, $role, $data['type'], $data['payload']);

    }

    /**
     * Indica se todas as candidaturas informadas já têm o live coding com o enunciado
     * publicado. É o que diz se a etapa de live coding pode começar.
     *
     * Diferente das outras etapas, o exercício é escrito por candidatura: basta uma
     * sem enunciado para a etapa inteira ficar na espera. Sem candidatura nenhuma não
     * há o que esperar, então a etapa pode começar vazia como as demais
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $applications
     */
    public function exercisesArePublished(Collection $applications): bool {

        $applicationIds = $applications->pluck('id')->all();

        if($applicationIds === []) {
            return true;
        }

        $publishedCount = DevJobVacancyLiveCoding::query()
            ->whereIn('dev_job_vacancy_id', $applicationIds)
            ->whereNotNull('statement')
            ->where('statement', '!=', '')
            ->distinct()
            ->count('dev_job_vacancy_id');

        return $publishedCount === count($applicationIds);

    }

    /**
     * Coloca em andamento a etapa de live coding de uma vaga que estava aguardando os
     * enunciados: as candidaturas paradas na espera passam para a etapa em si assim
     * que todas tiverem o exercício publicado.
     *
     * Vaga que não está nessa espera, ou que ainda tem algum enunciado faltando, não
     * é afetada.
     *
     * O dev não recebe uma notificação nova aqui: ele já foi avisado na publicação do
     * próprio exercício, que é o que ele precisa ler antes da call
     */
    private function startAwaitingLiveCodingStep(JobVacancy $jobVacancy): void {

        if($jobVacancy->process_step !== SelectionProcessStageEnum::AWAITING_LIVE_CODING) {
            return;
        }

        $applications = $jobVacancy->applications()
            ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
            ->where('process_step', SelectionProcessStageEnum::AWAITING_LIVE_CODING)
            ->get();

        if(!$this->exercisesArePublished($applications)) {
            return;
        }

        if($applications->isNotEmpty()) {
            DevJobVacancy::whereIn('id', $applications->pluck('id'))
                ->update(['process_step' => SelectionProcessStageEnum::LIVE_CODING->value]);
        }

        $jobVacancy->update(['process_step' => SelectionProcessStageEnum::LIVE_CODING->value]);

    }

    /**
     * Registra o horário proposto pela empresa e avisa o dev, deixando o live
     * coding aguardando a confirmação dele
     */
    private function proposeToDev(DevJobVacancyLiveCoding $liveCoding, array $data): DevJobVacancyLiveCodingResource {

        return DB::transaction(function() use ($liveCoding, $data) {

            $liveCoding->update([
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'duration_in_minutes' => $data['duration_in_minutes'],
                'status' => DevJobVacancyLiveCodingStatusEnum::AWAITING_DEV_CONFIRMATION
            ]);

            $this->notifyDev(
                $liveCoding,
                'live_coding_schedule_proposed',
                'Novo horário de live coding proposto',
                "A empresa {$liveCoding->jobVacancy?->companyProfile?->name} propôs um horário para o seu live coding da vaga {$liveCoding->jobVacancy?->title}: {$liveCoding->scheduled_at->format('d/m/Y H:i')}. Acesse a plataforma para confirmar ou propor outro horário."
            );

            return new DevJobVacancyLiveCodingResource($liveCoding);

        });

    }

    /**
     * Aprova o horário vigente do live coding e avisa a outra parte
     */
    private function approve(DevJobVacancyLiveCoding $liveCoding, bool $notifyCompany): DevJobVacancyLiveCodingResource {

        return DB::transaction(function() use ($liveCoding, $notifyCompany) {

            $liveCoding->update(['status' => DevJobVacancyLiveCodingStatusEnum::APPROVED]);

            $scheduledAt = $liveCoding->scheduled_at->format('d/m/Y H:i');
            $jobTitle = $liveCoding->jobVacancy?->title;

            if($notifyCompany) {
                $this->notifyCompany(
                    $liveCoding,
                    'live_coding_schedule_approved',
                    'Horário de live coding confirmado',
                    "O desenvolvedor {$liveCoding->devJobVacancy?->devProfile?->name} confirmou o horário do live coding da vaga {$jobTitle}: {$scheduledAt}."
                );
            } else {
                $this->notifyDev(
                    $liveCoding,
                    'live_coding_schedule_approved',
                    'Horário de live coding confirmado',
                    "A empresa {$liveCoding->jobVacancy?->companyProfile?->name} confirmou o horário do live coding da vaga {$jobTitle}: {$scheduledAt}."
                );
            }

            return new DevJobVacancyLiveCodingResource($liveCoding);

        });

    }

    private function notifyDev(DevJobVacancyLiveCoding $liveCoding, string $type, string $title, string $message): void {

        $liveCoding->devJobVacancy?->devProfile?->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => env('APP_URL') . "/job-vacancy/{$liveCoding->job_vacancy_id}"
        ]);

    }

    private function notifyCompany(DevJobVacancyLiveCoding $liveCoding, string $type, string $title, string $message): void {

        $liveCoding->jobVacancy?->companyProfile?->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => env('APP_URL') . "/job-vacancy/{$liveCoding->job_vacancy_id}"
        ]);

    }

    /**
     * Garante que o live coding pertence a uma vaga do perfil de empresa autenticado
     */
    private function getCompanyLiveCoding(string $id): DevJobVacancyLiveCoding {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't manage this live coding!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $liveCoding = DevJobVacancyLiveCoding::query()->with(self::RELATIONS)
            ->where('id', $id)
            ->whereHas('jobVacancy', fn($query) => $query->where('company_profile_id', $companyProfile->id))
            ->first();

        if(!$liveCoding) {
            throw new ApiException("This live coding does not belong to your company!", 403);
        }

        return $liveCoding;

    }

    /**
     * Garante que o live coding pertence à candidatura do perfil de dev autenticado
     */
    private function getDevLiveCoding(string $id): DevJobVacancyLiveCoding {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't manage this live coding!");
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $liveCoding = DevJobVacancyLiveCoding::query()->with(self::RELATIONS)
            ->where('id', $id)
            ->whereHas('devJobVacancy', fn($query) => $query->where('dev_profile_id', $devProfile->id))
            ->first();

        if(!$liveCoding) {
            throw new ApiException("This live coding does not belong to you!", 403);
        }

        return $liveCoding;

    }

    /**
     * Garante que o live coding pertence ao usuário autenticado, seja como dev ou
     * como empresa, e devolve qual dos dois papéis ele ocupa nele
     *
     * @return array{0: DevJobVacancyLiveCoding, 1: string}
     */
    private function getParticipantLiveCoding(string $id, User $authUser): array {

        $liveCoding = DevJobVacancyLiveCoding::query()->with(self::RELATIONS)->find($id);

        if(!$liveCoding) {
            throw new ApiException("Live coding not found!", 404);
        }

        if($authUser->dev_profile?->id === $liveCoding->devJobVacancy?->dev_profile_id) {
            return [$liveCoding, 'dev'];
        }

        if($authUser->company_profile?->id === $liveCoding->jobVacancy?->company_profile_id) {
            return [$liveCoding, 'company'];
        }

        throw new ApiException("This live coding does not belong to you!", 403);

    }

    /**
     * O enunciado não muda mais depois que o live coding é cancelado ou que a call
     * termina — não faz sentido trocar o exercício de uma avaliação já encerrada
     */
    private function assertNotFinished(DevJobVacancyLiveCoding $liveCoding): void {

        if(in_array($liveCoding->status, [
            DevJobVacancyLiveCodingStatusEnum::CANCELLED,
            DevJobVacancyLiveCodingStatusEnum::REJECTED
        ], true)) {
            throw new ApiException("This live coding is already cancelled or rejected!");
        }

        if($liveCoding->ended_at) {
            throw new ApiException("This live coding has already ended!");
        }

    }

    /**
     * Só libera a entrada na call dentro de uma janela em torno do horário
     * marcado, evitando entrar (ou ficar preso no canal) fora de hora
     */
    private function assertWithinJoinWindow(DevJobVacancyLiveCoding $liveCoding): void {

        if(!$liveCoding->scheduled_at) {
            throw new ApiException("This live coding does not have a scheduled time yet!");
        }

        $opensAt = $liveCoding->scheduled_at->copy()->subMinutes(self::JOIN_WINDOW_BEFORE_MINUTES);
        $closesAt = $liveCoding->scheduled_at->copy()
            ->addMinutes($liveCoding->duration_in_minutes ?? 0)
            ->addMinutes(self::JOIN_WINDOW_GRACE_AFTER_MINUTES);

        if(now()->lt($opensAt) || now()->gt($closesAt)) {
            throw new ApiException("It's not time for this live coding yet, or the join window has already closed!");
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
