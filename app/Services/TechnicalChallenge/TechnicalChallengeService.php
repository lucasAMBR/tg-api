<?php

namespace App\Services\TechnicalChallenge;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\DevTechnicalChallengeStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Enums\TranslationStatusEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevTechnicalChallenge\DevTechnicalChallengeResource;
use App\Http\Resources\TechnicalChallenge\TechnicalChallengeResource;
use App\Jobs\TranslateContentJob;
use App\Models\DevJobVacancy;
use App\Models\DevTechnicalChallenge;
use App\Models\JobVacancy;
use App\Models\TechnicalChallenge;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TechnicalChallengeService {

    /**
     * Relações que montam a entrega do ponto de vista do dev: o desafio a resolver
     * e o que ele já enviou
     */
    private const DEV_CHALLENGE_RELATIONS = ['technicalChallenge', 'devJobVacancy.jobVacancy'];

    /**
     * Empresa monta o desafio técnico da vaga, com o enunciado, as regras, o prazo de
     * entrega e, opcionalmente, o documento guia. Cada texto entra na fila de tradução
     */
    public function store(array $data): TechnicalChallengeResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $this->ensureVacancyHasTechnicalChallengeStep($jobVacancy);

        if($this->getVacancyChallenge($jobVacancy)) {
            throw new ApiException("This vacancy already has a technical challenge!");
        }

        return DB::transaction(function() use ($jobVacancy, $data) {

            $challenge = TechnicalChallenge::create([
                'job_vacancy_id' => $jobVacancy->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'rules' => $data['rules'] ?? null,
                'due_date' => Carbon::parse($data['due_date']),
                'accepts_zip_submission' => $data['accepts_zip_submission'] ?? false
            ]);

            TranslateContentJob::dispatch($challenge);

            if(isset($data['guide_document']) && $data['guide_document'] instanceof UploadedFile) {
                $this->saveGuideDocument($challenge, $data['guide_document']);
            }

            // A vaga pode ter avançado para a etapa antes do desafio existir. Nesse caso
            // as candidaturas estão paradas aguardando o enunciado e começam a resolver agora
            $this->startAwaitingTechnicalChallengeStep($jobVacancy, $challenge);

            return new TechnicalChallengeResource($challenge->fresh());

        });

    }

    /**
     * Desafio técnico da vaga, do ponto de vista da empresa
     */
    public function showByJobVacancy(array $data): TechnicalChallengeResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $challenge = $this->getVacancyChallenge($jobVacancy);

        if(!$challenge) {
            throw new ApiException("This vacancy does not have a technical challenge yet!", 404);
        }

        return new TechnicalChallengeResource($challenge->loadCount('devChallenges'));

    }

    /**
     * Empresa mantém o desafio: enunciado, regras, prazo e a permissão de envio do zip.
     *
     * Só é aceito enquanto a etapa não começou, ou seja, enquanto nenhum candidato
     * recebeu o desafio para resolver
     */
    public function update(array $data): TechnicalChallengeResource {

        $challenge = $this->getCompanyChallenge($data['id']);

        $this->ensureChallengeNotInUse($challenge);

        return DB::transaction(function() use ($challenge, $data) {

            $this->updateChallengeContent($challenge, $data);

            return new TechnicalChallengeResource($challenge->fresh());

        });

    }

    /**
     * Empresa remove o desafio da vaga, enquanto a etapa não começou
     */
    public function destroy(array $data): void {

        $challenge = $this->getCompanyChallenge($data['id']);

        $this->ensureChallengeNotInUse($challenge);

        DB::transaction(function() use ($challenge) {
            $challenge->clearMediaCollection(TechnicalChallenge::GUIDE_DOCUMENT_COLLECTION);

            $challenge->delete();
        });

    }

    /**
     * Anexa o documento guia ao desafio, substituindo o anterior quando já existe um.
     * Diferente do resto do desafio, o guia pode ser trocado mesmo com a etapa em
     * andamento, porque não muda o que foi combinado com os candidatos
     */
    public function saveGuideDocumentFile(array $data): TechnicalChallengeResource {

        $challenge = $this->getCompanyChallenge($data['id']);

        $this->saveGuideDocument($challenge, $data['guide_document']);

        return new TechnicalChallengeResource($challenge->fresh());

    }

    /**
     * Remove o documento guia do desafio
     */
    public function removeGuideDocument(array $data): TechnicalChallengeResource {

        $challenge = $this->getCompanyChallenge($data['id']);

        $challenge->clearMediaCollection(TechnicalChallenge::GUIDE_DOCUMENT_COLLECTION);

        return new TechnicalChallengeResource($challenge->fresh());

    }

    /**
     * Abre a entrega do desafio para cada candidatura que entrou na etapa de desafio
     * técnico e avisa o desenvolvedor do prazo. Sem desafio cadastrado na vaga não há
     * o que entregar e nada é criado
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $approved
     */
    public function createDevChallenges(JobVacancy $jobVacancy, Collection $approved, ?Carbon $dueDate = null): void {

        $challenge = $this->getVacancyChallenge($jobVacancy);

        if(!$challenge) {
            return;
        }

        // O prazo é o do desafio, definido pela empresa ao montar o enunciado
        $deliveryDate = $challenge->due_date ?? $dueDate;

        foreach($approved as $application) {

            // Uma única entrega por candidatura, mesmo que a etapa seja reprocessada
            DevTechnicalChallenge::firstOrCreate(
                [
                    'technical_challenge_id' => $challenge->id,
                    'dev_job_vacancy_id' => $application->id
                ],
                [
                    'dev_profile_id' => $application->dev_profile_id,
                    'due_date' => $deliveryDate
                ]
            );

            $application->devProfile?->notifications()->create([
                'type' => 'technical_challenge_created',
                'title' => 'Desafio técnico',
                'message' => "A empresa enviou o desafio técnico da vaga {$jobVacancy->title}. Entregue até {$deliveryDate->format('d/m/Y')} para seguir no processo seletivo!",
                'link' => env('APP_URL') . '/jobs'
            ]);

        }

    }

    /**
     * Desafio que o desenvolvedor autenticado tem para entregar, com o enunciado, as
     * regras, o documento guia e, se já tiver entregue, o que foi enviado
     */
    public function showDevChallenge(array $data): DevTechnicalChallengeResource {

        $devChallenge = $this->getDevChallenge($data['id']);

        return new DevTechnicalChallengeResource(
            $devChallenge->load(self::DEV_CHALLENGE_RELATIONS)
        );

    }

    /**
     * Desenvolvedor entrega o desafio técnico, informando o repositório com a solução
     * e, quando o desafio aceita, o projeto compactado
     */
    public function submit(array $data): DevTechnicalChallengeResource {

        $devChallenge = $this->getDevChallenge($data['id']);

        if($devChallenge->isDelivered()) {
            throw new ApiException("This technical challenge was already delivered!");
        }

        $application = $devChallenge->devJobVacancy;

        if($application?->status !== DevJobVacancyStatusEnum::IN_PROGRESS) {
            throw new ApiException("This application is no longer in progress!");
        }

        if($application->process_step !== SelectionProcessStageEnum::TECHNICAL_CHALLENGE) {
            throw new ApiException("This application is not in the technical challenge step!");
        }

        $submissionFile = $data['submission_file'] ?? null;

        // O zip só é aceito nos desafios em que a empresa liberou o envio compactado
        if($submissionFile && !$devChallenge->technicalChallenge?->accepts_zip_submission) {
            throw new ApiException("This technical challenge does not accept a zip submission!");
        }

        return DB::transaction(function() use ($devChallenge, $data, $submissionFile) {

            if($submissionFile instanceof UploadedFile) {
                $devChallenge->addMedia($submissionFile)
                    ->toMediaCollection(DevTechnicalChallenge::SUBMISSION_COLLECTION);
            }

            $devChallenge->update([
                'repository_url' => $data['repository_url'],
                'status' => DevTechnicalChallengeStatusEnum::DELIVERED,
                'submitted_at' => now()
            ]);

            $this->notifyCompany($devChallenge);

            return new DevTechnicalChallengeResource(
                $devChallenge->fresh()->load(self::DEV_CHALLENGE_RELATIONS)
            );

        });

    }

    /**
     * Desafio vigente da vaga, ou null quando ela ainda não tem um. É o que diz se a
     * etapa de desafio técnico pode começar
     */
    public function getVacancyChallenge(JobVacancy $jobVacancy): ?TechnicalChallenge {

        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return TechnicalChallenge::query()
            ->where('job_vacancy_id', $jobVacancy->id)
            ->latest('created_at')
            ->first();

    }

    /**
     * Coloca em andamento a etapa de desafio técnico de uma vaga que estava aguardando
     * o cadastro do desafio: as candidaturas paradas na espera passam para a etapa em
     * si e recebem o enunciado para resolver.
     *
     * Vaga que não está nessa espera não é afetada
     */
    private function startAwaitingTechnicalChallengeStep(JobVacancy $jobVacancy, TechnicalChallenge $challenge): void {

        if($jobVacancy->process_step !== SelectionProcessStageEnum::AWAITING_TECHNICAL_CHALLENGE) {
            return;
        }

        $applications = $jobVacancy->applications()->with('devProfile')
            ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
            ->where('process_step', SelectionProcessStageEnum::AWAITING_TECHNICAL_CHALLENGE)
            ->get();

        if($applications->isNotEmpty()) {
            DevJobVacancy::whereIn('id', $applications->pluck('id'))
                ->update(['process_step' => SelectionProcessStageEnum::TECHNICAL_CHALLENGE->value]);
        }

        $jobVacancy->update(['process_step' => SelectionProcessStageEnum::TECHNICAL_CHALLENGE->value]);

        $this->createDevChallenges($jobVacancy, $applications, $challenge->due_date);

    }

    /**
     * Atualiza o conteúdo do desafio. Os textos só voltam para a fila de tradução
     * quando mudam de fato, para não retraduzir o que já está traduzido
     */
    private function updateChallengeContent(TechnicalChallenge $challenge, array $data): void {

        $rules = $data['rules'] ?? null;

        $contentChanged = $challenge->title !== $data['title']
            || $challenge->description !== $data['description']
            || $challenge->rules !== $rules;

        $challenge->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'rules' => $rules,
            'due_date' => Carbon::parse($data['due_date']),
            'accepts_zip_submission' => $data['accepts_zip_submission'] ?? false,
            'translation_status' => $contentChanged
                ? TranslationStatusEnum::PENDING->value
                : $challenge->translation_status
        ]);

        if($contentChanged) {
            TranslateContentJob::dispatch($challenge);
        }

    }

    /**
     * Guarda o documento guia na coleção do desafio, que é de arquivo único: o envio
     * substitui o documento anterior
     */
    private function saveGuideDocument(TechnicalChallenge $challenge, UploadedFile $guideDocument): void {

        $challenge->addMedia($guideDocument)
            ->toMediaCollection(TechnicalChallenge::GUIDE_DOCUMENT_COLLECTION);

    }

    /**
     * Avisa a empresa de que o candidato entregou o desafio técnico
     */
    private function notifyCompany(DevTechnicalChallenge $devChallenge): void {

        $jobVacancy = $devChallenge->devJobVacancy?->jobVacancy;
        $devName = $devChallenge->devProfile?->name;

        $jobVacancy?->companyProfile?->notifications()->create([
            'type' => 'technical_challenge_delivered',
            'title' => 'Desafio técnico entregue',
            'message' => "O desenvolvedor {$devName} entregou o desafio técnico da vaga {$jobVacancy->title}.",
            'link' => env('APP_URL') . "/job-vacancy/{$jobVacancy->id}"
        ]);

    }

    /**
     * O desafio só pode ser alterado enquanto a etapa não começou, ou seja, enquanto
     * nenhum candidato o recebeu para resolver
     */
    private function ensureChallengeNotInUse(TechnicalChallenge $challenge): void {

        if($challenge->devChallenges()->exists()) {
            throw new ApiException("This technical challenge was already sent to the candidates and can't be changed anymore!");
        }

    }

    /**
     * O desafio só faz sentido em uma vaga que tem a etapa de desafio técnico no
     * processo seletivo
     */
    private function ensureVacancyHasTechnicalChallengeStep(JobVacancy $jobVacancy): void {

        $hasStep = $jobVacancy->processSteps()
            ->where('step', SelectionProcessStageEnum::TECHNICAL_CHALLENGE->value)
            ->exists();

        if(!$hasStep) {
            throw new ApiException("This vacancy does not have the technical challenge step in its selection process!");
        }

    }

    /**
     * Garante que a vaga pertence ao perfil de empresa autenticado
     */
    private function getCompanyJobVacancy(string $jobVacancyId): JobVacancy {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't manage the technical challenge of this vacancy!", 403);
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $jobVacancy = JobVacancy::query()
            ->where('id', $jobVacancyId)
            ->where('company_profile_id', $companyProfile->id)
            ->first();

        if(!$jobVacancy) {
            throw new ApiException("This vacancy does not belong to your company!", 403);
        }

        return $jobVacancy;

    }

    /**
     * Garante que o desafio é de uma vaga do perfil de empresa autenticado
     */
    private function getCompanyChallenge(string $challengeId): TechnicalChallenge {

        $challenge = TechnicalChallenge::findOrFail($challengeId);

        $this->getCompanyJobVacancy($challenge->job_vacancy_id);

        return $challenge;

    }

    /**
     * Garante que a entrega é do desenvolvedor autenticado
     */
    private function getDevChallenge(string $devChallengeId): DevTechnicalChallenge {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't deliver this technical challenge!", 403);
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $devChallenge = DevTechnicalChallenge::query()
            ->with(['devJobVacancy.jobVacancy', 'devProfile', 'technicalChallenge'])
            ->where('id', $devChallengeId)
            ->where('dev_profile_id', $devProfile->id)
            ->first();

        if(!$devChallenge) {
            throw new ApiException("This technical challenge does not belong to you!", 403);
        }

        return $devChallenge;

    }

}
