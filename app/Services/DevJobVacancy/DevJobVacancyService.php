<?php 

namespace App\Services\DevJobVacancy;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\PortfolioSolicitationStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevJobVacancy\DevJobVacancyCollection;
use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Models\DevJobVacancy;
use App\Models\JobVacancy;
use App\Models\JobVacancyProcessStep;
use App\Models\PortfolioSolicitation;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevJobVacancyService {

    /**
     * Prazo padrão, em dias, para o envio do portfólio quando a empresa não informa
     * uma data na virada da etapa
     */
    private const PORTFOLIO_DUE_DAYS = 7;

    public function apply(array $data): DevJobVacancyResource {

        $authUser = Auth::user();

        // Verifica a role
        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't apply for this vacancy!");
        }

        // Carrega o perfil do usuário
        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        if (!$devProfile) {
            throw new ApiException("Developer profile not found!");
        }

        $jobVacancy = JobVacancy::findOrFail($data['job_vacancy_id']);

        // Só aceita candidaturas enquanto as inscrições estiverem abertas
        if(!$jobVacancy->acceptsApplications()) {
            throw new ApiException('This vacancy is not accepting applications anymore!');
        }

        $vacancy = DevJobVacancy::where('dev_profile_id', $devProfile->id)
            ->where('job_vacancy_id', $jobVacancy->id)
            ->exists();
            
        // Valida se o usuário ja esta inscrito nessa vaga
        if($vacancy) {
            throw new ApiException('Developer already applied for this vacancy!');
        }

        return DB::transaction(function () use ($devProfile, $jobVacancy) {

            $application = DevJobVacancy::create([
                'dev_profile_id' => $devProfile->id,
                'job_vacancy_id' => $jobVacancy->id,
                'status' => DevJobVacancyStatusEnum::IN_PROGRESS,
                // A candidatura entra no mesmo passo em que a vaga se encontra
                'process_step' => $jobVacancy->process_step,
            ]);

            $application->refresh();
            $application->load(['jobVacancy', 'devProfile']);

            return new DevJobVacancyResource($application);

        });

    }

    public function indexApplies(array $data): DevJobVacancyCollection {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't index applies for this vacancy!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $page = $data['page'] ?? 1;
        $per_page = $data['per_page'] ?? 10;
        $search = $data['search'] ?? '';
        $status = $data['status'] ?? null;
        $jobVacancyId = $data['job_vacancy_id'] ?? null;

        $applies = DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
        ->whereHas('jobVacancy', function($query) use ($companyProfile) {
            $query->where('company_profile_id', $companyProfile->id);
        })
        ->when($status, function($query) use ($status) {
            $query->where('status', $status);
        })
        // Restringe as candidaturas a uma única vaga da empresa
        ->when($jobVacancyId, function($query) use ($jobVacancyId) {
            $query->where('job_vacancy_id', $jobVacancyId);
        })
        ->when($search, function($query) use ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('devProfile', function($devQuery) use ($search) {
                    $devQuery->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('jobVacancy', function($companyQuery) use ($search) {
                    $companyQuery->where('title', 'ILIKE', "%{$search}%");
                });
            });
        })->paginate(
            $per_page,
            ['*'],
            'page',
            $page
        );

        return new DevJobVacancyCollection($applies);

    }

    /**
     * Lista as candidaturas do desenvolvedor autenticado
     */
    public function indexMyApplies(array $data): DevJobVacancyCollection {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't index your applies!");
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $page = $data['page'] ?? 1;
        $per_page = $data['per_page'] ?? 10;
        $search = $data['search'] ?? '';
        $status = $data['status'] ?? null;

        // As candidaturas podem estar em etapas diferentes, então carrega os dados de todas
        $applies = DevJobVacancy::query()->with(array_merge(['jobVacancy.companyProfile'], $this->stepRelations()))
        ->where('dev_profile_id', $devProfile->id)
        ->when($status, function($query) use ($status) {
            $query->where('status', $status);
        })
        ->when($search, function($query) use ($search) {
            $query->whereHas('jobVacancy', function($vacancyQuery) use ($search) {
                $vacancyQuery->where('title', 'ILIKE', "%{$search}%")
                ->orWhereHas('companyProfile', function($companyQuery) use ($search) {
                    $companyQuery->where('name', 'ILIKE', "%{$search}%");
                });
            });
        })
        ->latest()
        ->paginate(
            $per_page,
            ['*'],
            'page',
            $page
        );

        return new DevJobVacancyCollection($applies);

    }

    /**
     * Lista as candidaturas em andamento que estão paradas em um passo do processo seletivo
     */
    public function indexStepApplies(array $data): AnonymousResourceCollection {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $search = $data['search'] ?? '';
        $processStep = $data['process_step'] ?? SelectionProcessStageEnum::RESUME_SCREENING->value;

        // Só os dados da etapa consultada, ex.: o envio do portfólio na análise de portfólio
        $relations = array_merge(['jobVacancy', 'devProfile'], $this->stepRelations($processStep));

        $applies = DevJobVacancy::query()->with($relations)
        ->where('job_vacancy_id', $jobVacancy->id)
        ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
        ->where('process_step', $processStep)
        ->when($search, function($query) use ($search) {
            $query->whereHas('devProfile', function($devQuery) use ($search) {
                $devQuery->where('name', 'ILIKE', "%{$search}%");
            });
        })
        ->latest()
        ->get();

        return DevJobVacancyResource::collection($applies);

    }

    /**
     * Retorna as candidaturas aprovadas em um passo do processo seletivo e as recusadas
     * nesse mesmo passo.
     *
     * Aprovado no passo é quem seguiu adiante: as candidaturas que já estão em um passo
     * posterior e as que chegaram ao fim do processo com o status `approved`
     *
     * @return array{approved: DevJobVacancyResource[], rejected_in_step: DevJobVacancyResource[]}
     */
    public function stepResults(array $data): array {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $processStep = $data['process_step'] ?? SelectionProcessStageEnum::RESUME_SCREENING->value;

        $laterSteps = $this->getStepsAfter($jobVacancy, $processStep);

        $applies = DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
            ->where('job_vacancy_id', $jobVacancy->id)
            ->where(function($query) use ($processStep, $laterSteps) {
                $query->where('status', DevJobVacancyStatusEnum::APPROVED)
                    // Ainda em andamento, mas já em um passo posterior ao consultado
                    ->when($laterSteps !== [], function($approvedQuery) use ($laterSteps) {
                        $approvedQuery->orWhere(function($inProgressQuery) use ($laterSteps) {
                            $inProgressQuery->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
                                ->whereIn('process_step', $laterSteps);
                        });
                    })
                    ->orWhere(function($rejectedQuery) use ($processStep) {
                        $rejectedQuery->where('status', DevJobVacancyStatusEnum::REJECTED)
                            ->where('process_step', $processStep);
                    });
            })
            ->latest()
            ->get();

        [$rejected, $approved] = $applies->partition(
            fn(DevJobVacancy $apply) => $apply->status === DevJobVacancyStatusEnum::REJECTED
        );

        return [
            'approved' => DevJobVacancyResource::collection($approved->values()),
            'rejected_in_step' => DevJobVacancyResource::collection($rejected->values())
        ];

    }

    /**
     * Relações com os dados próprios de cada etapa do processo seletivo. Sem passo
     * informado devolve todas, para acompanhar candidaturas em etapas diferentes.
     *
     * NOTA: cada nova etapa que passar a ter dados próprios entra nesse mapa
     *
     * @return array<int, string>
     */
    private function stepRelations(?string $processStep = null): array {

        $relations = [
            SelectionProcessStageEnum::PORTFOLIO_REVIEW->value => 'portfolioSolicitation'
        ];

        if($processStep === null) {
            return array_values($relations);
        }

        return isset($relations[$processStep]) ? [$relations[$processStep]] : [];

    }

    /**
     * Passos configurados na vaga que vêm depois do passo informado
     *
     * @return array<int, string>
     */
    private function getStepsAfter(JobVacancy $jobVacancy, string $processStep): array {

        $steps = $jobVacancy->processSteps()->get()->pluck('step')->all();

        $currentIndex = array_search($processStep, $steps, true);

        // Passo que não faz parte do processo seletivo da vaga não tem passos posteriores
        if($currentIndex === false) {
            return [];
        }

        return array_values(array_slice($steps, $currentIndex + 1));

    }

    /**
     * Avança as candidaturas da etapa atual da vaga: as informadas em `apply_ids`
     * são aprovadas na etapa e seguem para a próxima, e todas as demais candidaturas
     * em andamento naquela etapa são recusadas.
     *
     * NOTA: por enquanto o avanço é puramente manual, decidido pela empresa. Novas
     * regras de avanço serão implementadas conforme cada etapa do processo seletivo
     * for melhor desenvolvida (ex.: exigir a correção do teste de proficiência nas
     * perguntas de triagem, entrevista realizada, desafio técnico entregue etc.).
     *
     * @return array{
     *     process_step: string|null,
     *     approved: DevJobVacancyResource[],
     *     rejected: DevJobVacancyResource[]
     * }
     */
    public function advanceStep(array $data): array {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        // O processo seletivo só começa depois que as inscrições são encerradas
        if($jobVacancy->acceptsApplications()) {
            throw new ApiException("You need to close the inscriptions before advancing the selection process!");
        }

        $currentStep = $jobVacancy->process_step;
        $nextStep = $this->getNextProcessStep($jobVacancy);

        $applies = $jobVacancy->applications()
            ->with(['jobVacancy', 'devProfile'])
            ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
            ->where('process_step', $currentStep)
            ->get();

        if($applies->isEmpty()) {
            throw new ApiException("There are no applications in progress in the current step of this vacancy!");
        }

        $approvedIds = array_values(array_unique($data['apply_ids']));

        // Os ids enviados precisam ser candidaturas em andamento na etapa atual da vaga
        $unknownIds = array_diff($approvedIds, $applies->pluck('id')->all());

        if($unknownIds !== []) {
            throw new ApiException(
                "Some of the informed applications are not in progress in the current step of this vacancy!",
                400,
                ['apply_ids' => array_values($unknownIds)]
            );
        }

        [$approved, $rejected] = $applies->partition(
            fn(DevJobVacancy $apply) => in_array($apply->id, $approvedIds, true)
        );

        // Prazo de entrega usado quando a próxima etapa exige o envio do portfólio
        $dueDate = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : now()->addDays(self::PORTFOLIO_DUE_DAYS);

        return DB::transaction(function() use ($jobVacancy, $approved, $rejected, $currentStep, $nextStep, $dueDate) {

            // A recusa mantém o passo em que aconteceu
            if($rejected->isNotEmpty()) {
                DevJobVacancy::whereIn('id', $rejected->pluck('id'))
                    ->update(['status' => DevJobVacancyStatusEnum::REJECTED->value]);
            }

            if($approved->isNotEmpty()) {
                DevJobVacancy::whereIn('id', $approved->pluck('id'))->update(
                    // Sem próxima etapa a candidatura chega ao fim do processo aprovada
                    $nextStep
                        ? ['process_step' => $nextStep->value]
                        : ['status' => DevJobVacancyStatusEnum::APPROVED->value]
                );
            }

            if($nextStep) {
                $jobVacancy->update(['process_step' => $nextStep->value]);
            }

            $this->notifyStepAdvance($jobVacancy, $approved, $rejected, $currentStep, $nextStep);

            // A análise de portfólio começa com a solicitação do portfólio de cada aprovado
            if($nextStep === SelectionProcessStageEnum::PORTFOLIO_REVIEW) {
                $this->requestPortfolios($jobVacancy, $approved, $dueDate);
            }

            return [
                'process_step' => $nextStep?->value,
                'approved' => DevJobVacancyResource::collection(
                    DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
                        ->whereIn('id', $approved->pluck('id'))
                        ->get()
                ),
                'rejected' => DevJobVacancyResource::collection(
                    DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
                        ->whereIn('id', $rejected->pluck('id'))
                        ->get()
                )
            ];

        });

    }

    /**
     * Abre uma solicitação de portfólio para cada candidatura que entrou na etapa de
     * análise de portfólio e avisa o desenvolvedor do prazo de envio
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $approved
     */
    private function requestPortfolios(JobVacancy $jobVacancy, Collection $approved, Carbon $dueDate): void {

        foreach($approved as $application) {

            // Uma única solicitação por candidatura, mesmo que a etapa seja reprocessada
            PortfolioSolicitation::firstOrCreate(
                ['dev_job_vacancy_id' => $application->id],
                [
                    'dev_profile_id' => $application->dev_profile_id,
                    'status' => PortfolioSolicitationStatusEnum::PENDING,
                    'due_date' => $dueDate
                ]
            );

            $application->devProfile?->notifications()->create([
                'type' => 'portfolio_solicitation_created',
                'title' => 'Solicitação de portfólio',
                'message' => "A empresa está solicitando o seu portfólio para a etapa de análise de portfólio da vaga {$jobVacancy->title}. Envie o link até {$dueDate->format('d/m/Y')}!",
                'link' => env('APP_URL') . '/jobs'
            ]);

        }

    }

    /**
     * Avisa cada desenvolvedor sobre o resultado da etapa: os aprovados recebem a
     * próxima etapa que vai se iniciar (ou a aprovação final, quando não há próxima)
     * e os eliminados são avisados de que não seguem no processo seletivo
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $approved
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $rejected
     */
    private function notifyStepAdvance(
        JobVacancy $jobVacancy,
        Collection $approved,
        Collection $rejected,
        ?SelectionProcessStageEnum $currentStep,
        ?SelectionProcessStageEnum $nextStep
    ): void {

        $link = env('APP_URL') . "/job-vacancy/{$jobVacancy->id}";
        $currentStepLabel = $currentStep?->labelPt();

        foreach($approved as $application) {

            $message = $nextStep
                ? "Parabéns! Você foi aprovado na etapa de {$currentStepLabel} da vaga {$jobVacancy->title} e em breve a etapa de {$nextStep->labelPt()} vai se iniciar!"
                : "Parabéns! Você foi aprovado na etapa de {$currentStepLabel} e concluiu o processo seletivo da vaga {$jobVacancy->title}!";

            $application->devProfile?->notifications()->create([
                'type' => $nextStep ? 'job_vacancy_step_approved' : 'job_vacancy_process_approved',
                'title' => $nextStep ? 'Aprovado na etapa' : 'Aprovado no processo seletivo',
                'message' => $message,
                'link' => $link
            ]);

        }

        foreach($rejected as $application) {

            $application->devProfile?->notifications()->create([
                'type' => 'job_vacancy_step_rejected',
                'title' => 'Processo seletivo encerrado',
                'message' => "Infelizmente você não prosseguiu no processo seletivo da vaga {$jobVacancy->title} na etapa de {$currentStepLabel}. Agradecemos a sua participação!",
                'link' => $link
            ]);

        }

    }

    /**
     * Próxima etapa configurada no processo seletivo da vaga, ou null quando a
     * etapa atual é a última
     */
    private function getNextProcessStep(JobVacancy $jobVacancy): ?SelectionProcessStageEnum {

        $steps = $jobVacancy->processSteps()->get();

        $currentIndex = $steps->search(
            fn(JobVacancyProcessStep $step) => $step->step === $jobVacancy->process_step?->value
        );

        if($currentIndex === false) {
            throw new ApiException("The current step of this vacancy is not configured in its selection process!");
        }

        $nextStep = $steps->get($currentIndex + 1);

        return $nextStep ? SelectionProcessStageEnum::from($nextStep->step) : null;

    }

    /**
     * Garante que a vaga pertence ao perfil de empresa autenticado
     */
    private function getCompanyJobVacancy(string $jobVacancyId): JobVacancy {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't index applies for this vacancy!");
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

    public function reviewApply(array $data): DevJobVacancyResource {

        $authUser = Auth::user();
        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $apply = DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
            ->where('id', $data['id'])
            ->whereHas('jobVacancy', function($query) use ($companyProfile) {
                $query->where('company_profile_id', $companyProfile->id);
            })->first();

        if(!$apply) {
            throw new ApiException("This apply does not belong to your company!", 403);
        }

        return DB::transaction(function() use ($apply, $data) {

            // Só altera o que foi enviado, permitindo limpar o feedback com null
            $apply->update(Arr::only($data, ['status', 'feedback']));

            return new DevJobVacancyResource($apply);

        });

    }

}
