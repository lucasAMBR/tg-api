<?php

namespace App\Services\JobVacancy;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\JobVacancyStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Jobs\GenerateJobVacancyEmbeddingJob;
use App\Jobs\TranslateContentJob;
use App\Models\JobVacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobVacancyService {

    public function index(array $data) {

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;

        /**
         * Armazeno o id do profile da empresa baseado no que foi passado
         * via request
         */
        $company_id = $data['company_profile_id'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $status = $data['status'] ?? null;

        $jobVacancy = JobVacancy::query()->with('softSkill', 'languages', 'processSteps', 'companyProfile')
        ->when(isset($company_id), function(Builder $query) use ($company_id) {
            $query->where('company_profile_id', $company_id);
        })
        ->when(isset($seniorityLevel), function(Builder $query) use ($seniorityLevel) {
            $query->where('seniority_level', $seniorityLevel);
        })
        ->when(isset($status), function(Builder $query) use ($status) {
            $query->where('status', $status);
        })
        ->when($search, function(Builder $query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('contract_type', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%")
                ->orWhereHas('languages', function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('softSkill', function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
            });
        })->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );

        return $jobVacancy;

    }

    public function store(Array $data){

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You must be a company for create a job vacancy");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        if(!$companyProfile) {
            throw new ApiException("You don't have an active profile!");
        }

        return DB::transaction(function() use ($data, $companyProfile) {

            $jobVacancy = JobVacancy::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'employment_type' => $data['employment_type'],
                'benefits' => $data['benefits'],
                'estimated_salary' => $data['estimated_salary'],
                'contract_type' => $data['contract_type'],
                'seniority_level' => $data['seniority_level'],
                'specialties' => $data['specialties'],
                'company_profile_id' => $companyProfile->id
            ]);

        
            // Percorre pelo array de linguages e salva no banco
            foreach($data['languages'] as $language) {
                $jobVacancy->languages()->attach($language['languages_id'], [
                    'language_level' => $language['language_level']
                ]);
            }

            // Como não é uma tabela complexa eu armazeno o id passado na pivot
            $jobVacancy->softSkill()->sync(
                collect($data['soft_skills'])->pluck('soft_skills_id')
            );
            
            $jobVacancy->desirableLanguage()->sync($data['languages_desirable']);

            // Etapas do processo seletivo da vaga
            $jobVacancy->processSteps()->createMany(
                collect($data['process_steps'])->map(fn(array $processStep) => [
                    'step' => $processStep['step'],
                    'order' => $processStep['order']
                ])->all()
            );

            $jobVacancy->refresh();
            GenerateJobVacancyEmbeddingJob::dispatchDebounced($jobVacancy->id);
            TranslateContentJob::dispatch($jobVacancy);

            // Retorna ja com as relações carregadas
            return $jobVacancy->load('languages', 'softSkill', 'desirableLanguage', 'companyProfile', 'processSteps');

        });

    }

    public function show(array $data) {

        $jobVacancy = JobVacancy::findOrFail($data['id']);

        // load() porque ja carrega a relação sem precisar consultar novamente o banco
        return $jobVacancy
            ->load(['softSkill', 'languages', 'desirableLanguage', 'companyProfile', 'processSteps'])
            ->loadCount('devProfiles');

    }

    public function update(array $data) {

        $jobVacancy = JobVacancy::findOrFail($data['id']);

        $data = Arr::except($data, ['id']);

        return DB::transaction(function() use ($data, $jobVacancy) {

            // VAGA
            $jobVacancy->update(Arr::only($data, [
                'title',
                'description',
                'employment_type',
                'benefits',
                'estimated_salary',
                'contract_type',
                'seniority_level',
                'specialties',
            ]));

            // LINGUAGEM E NIVEL
            foreach($data['languages'] ?? [] as $language) {

                // Atualiza a linguagem e caso tenha o nível atualiza também
                if(
                    isset($language['new_languages_id']) &&
                    isset($language['current_languages_id'])
                ) {
                    $jobVacancy->languages()->detach($language['current_languages_id']);
                    $jobVacancy->languages()->attach($language['new_languages_id'],
                    [
                        'language_level' => $language['language_level'] ?? null
                    ]);
                }

                // Atualiza somente o nivel caso não tenha o id da nova
                elseif(
                    isset($language['language_level']) &&
                    isset($language['current_languages_id'])
                ) {
                    $jobVacancy->languages()->updateExistingPivot($language['current_languages_id'], // Pede o id de comparação e depois um array associativo com os campos
                    ['language_level' => $language['language_level']]);
                }

            }

            // SOFT SKILL
            foreach($data['soft_skills'] ?? [] as $softSkill) {
                if(
                    isset($softSkill['new_soft_skills_id']) &&
                    isset($softSkill['current_soft_skills_id'])
                ) {
                    $jobVacancy->softSkill()->detach($softSkill['current_soft_skills_id']);
                    $jobVacancy->softSkill()->attach($softSkill['new_soft_skills_id']);
                }
            }

            $jobVacancy->refresh();
            GenerateJobVacancyEmbeddingJob::dispatchDebounced($jobVacancy->id);

            if (isset($data['title']) || isset($data['description']) || isset($data['benefits'])) {
                TranslateContentJob::dispatch($jobVacancy);
            }

            return $jobVacancy->fresh(['softSkill', 'languages', 'processSteps']);

        });
    }

    /**
     * Atualiza o status da vaga respeitando as transições permitidas
     */
    public function updateStatus(array $data) {

        $jobVacancy = $this->findCompanyJobVacancy($data['id']);
        $newStatus = JobVacancyStatusEnum::from($data['status']);

        $this->ensureStatusTransition($jobVacancy, $newStatus);

        if($newStatus === JobVacancyStatusEnum::CLOSED_INSCRIPTIONS) {
            return $this->applyInscriptionsClosure($jobVacancy);
        }

        return DB::transaction(function() use ($jobVacancy, $newStatus) {

            $jobVacancy->update(['status' => $newStatus]);

            return $jobVacancy->fresh(['softSkill', 'languages', 'processSteps']);

        });

    }

    /**
     * Encerra as inscrições da vaga, iniciando a análise curricular
     */
    public function closeInscriptions(array $data) {

        $jobVacancy = $this->findCompanyJobVacancy($data['id']);

        $this->ensureStatusTransition($jobVacancy, JobVacancyStatusEnum::CLOSED_INSCRIPTIONS);

        return $this->applyInscriptionsClosure($jobVacancy);

    }

    /**
     * Carrega a vaga garantindo que ela pertence à empresa autenticada
     */
    private function findCompanyJobVacancy(string $jobVacancyId): JobVacancy {

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You must be a company to manage a job vacancy status!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $jobVacancy = JobVacancy::query()
            ->where('id', $jobVacancyId)
            ->where('company_profile_id', $companyProfile->id)
            ->first();

        if(!$jobVacancy) {
            throw new ApiException("This job vacancy does not belong to your company!", 403);
        }

        return $jobVacancy;

    }

    /**
     * Valida se a vaga pode transitar do status atual para o novo status
     */
    private function ensureStatusTransition(JobVacancy $jobVacancy, JobVacancyStatusEnum $newStatus): void {

        $currentStatus = $jobVacancy->status;

        if($currentStatus === $newStatus) {
            throw new ApiException("This job vacancy already has this status!");
        }

        if(!in_array($newStatus, $currentStatus->allowedTransitions(), true)) {
            throw new ApiException(
                "You can't change the job vacancy status from '{$currentStatus->value}' to '{$newStatus->value}'!"
            );
        }

    }

    /**
     * Encerra as inscrições: a vaga para de aceitar candidaturas, ela e as
     * candidaturas em andamento avançam para a triagem de currículos e os
     * desenvolvedores inscritos são notificados
     */
    private function applyInscriptionsClosure(JobVacancy $jobVacancy) {

        return DB::transaction(function() use ($jobVacancy) {

            $jobVacancy->update([
                'status' => JobVacancyStatusEnum::CLOSED_INSCRIPTIONS,
                'process_step' => SelectionProcessStageEnum::RESUME_SCREENING
            ]);

            $applications = $jobVacancy->applications()
                ->with('devProfile')
                ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
                ->get();

            $jobVacancy->applications()
                ->whereIn('id', $applications->pluck('id'))
                ->update(['process_step' => SelectionProcessStageEnum::RESUME_SCREENING->value]);

            $this->notifyInscriptionsClosure($jobVacancy, $applications);

            return $jobVacancy->fresh(['softSkill', 'languages', 'processSteps']);

        });

    }

    /**
     * Avisa cada desenvolvedor inscrito que as inscrições foram encerradas
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, \App\Models\DevJobVacancy> $applications
     */
    private function notifyInscriptionsClosure(JobVacancy $jobVacancy, Collection $applications): void {

        foreach($applications as $application) {

            $application->devProfile?->notifications()->create([
                'type' => 'job_vacancy_inscriptions_closed',
                'title' => 'Inscrições encerradas',
                'message' => "As inscrições da vaga {$jobVacancy->title} foram encerradas e a análise curricular vai se iniciar!",
                'link' => env('APP_URL') . "/job-vacancy/{$jobVacancy->id}"
            ]);

        }

    }

    public function destroy(array $data) {

        $jobVacancy = JobVacancy::findOrFail($data['id']);

        return DB::transaction(function() use($jobVacancy) {

            $jobVacancy->delete();
            $jobVacancy->languages()->detach();
            $jobVacancy->softSkill()->detach();

            return $jobVacancy;

        });

    }
}
