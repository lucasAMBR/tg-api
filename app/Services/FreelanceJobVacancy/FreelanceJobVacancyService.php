<?php

namespace App\Services\FreelanceJobVacancy;

use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\FreelanceJobVacancy\FreelanceJobVacancyCollection;
use App\Jobs\GenerateFreelanceJobVacancyEmbeddingJob;
use App\Models\FreelanceJobVacancy;
use App\Models\User;
use App\Traits\InteractsWithFreelanceJobVacancies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FreelanceJobVacancyService
{
    use InteractsWithFreelanceJobVacancies;

    /**
     * Vitrine de freelas: só os abertos, que ainda recebem orçamento
     */
    public function index(array $data): FreelanceJobVacancyCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;
        $jobType = $data['job_type'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $onlyInvited = $data['invited'] ?? false;

        $freelanceJobVacancies = $this->openVacanciesQuery($search)
            ->when($jobType, fn(Builder $query, $jobType) => $query->where('job_type', $jobType))
            ->when($seniorityLevel, fn(Builder $query, $seniorityLevel) => $query->where('seniority_level', $seniorityLevel))
            // Os freelas para os quais o dev autenticado foi convidado a orçar
            ->when($onlyInvited, function (Builder $query) {
                $devProfile = $this->resolveDevProfile();

                $query->whereHas('invitations', fn(Builder $q) => $q->where('dev_profile_id', $devProfile->id));
            })
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceJobVacancyCollection($freelanceJobVacancies);
    }

    /**
     * Freelas publicados pelo cliente autenticado, em qualquer status, com quantos
     * orçamentos cada um recebeu e o contrato dos que já foram fechados
     */
    public function indexMyVacancies(array $data): FreelanceJobVacancyCollection
    {
        $clientProfile = $this->resolveClientProfile();

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;
        $status = $data['status'] ?? null;

        $freelanceJobVacancies = $this->query()
            ->with('acceptedProposal.devProfile.user')
            ->withCount('proposals')
            ->where('client_profile_id', $clientProfile->id)
            ->when($status, fn(Builder $query, $status) => $query->where('status', $status))
            ->when($search, fn(Builder $query, $search) => $this->applySearch($query, $search))
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceJobVacancyCollection($freelanceJobVacancies);
    }

    /**
     * Freelas em que o dev autenticado foi contratado: os em andamento primeiro, depois
     * os concluídos, cada grupo do contrato mais recente para o mais antigo
     */
    public function indexMyContracts(array $data): FreelanceJobVacancyCollection
    {
        $devProfile = $this->resolveDevProfile();

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;
        $status = $data['status'] ?? null;

        $freelanceJobVacancies = $this->query()
            ->with('acceptedProposal.devProfile.user')
            ->whereHas('acceptedProposal', fn(Builder $query) => $query->where('dev_profile_id', $devProfile->id))
            ->when($status, fn(Builder $query, $status) => $query->where('status', $status))
            ->when($search, fn(Builder $query, $search) => $this->applySearch($query, $search))
            ->orderByRaw('case when status = ? then 0 else 1 end', [FreelanceJobVacancyStatusEnum::IN_PROGRESS->value])
            ->latest('contracted_at')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceJobVacancyCollection($freelanceJobVacancies);
    }

    public function store(array $data)
    {
        $clientProfile = $this->resolveClientProfile();

        $freelanceJobVacancy = DB::transaction(function () use ($data, $clientProfile) {

            $freelanceJobVacancy = FreelanceJobVacancy::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'job_type' => $data['job_type'],
                'budget' => $data['budget'],
                'seniority_level' => $data['seniority_level'],
                'specialties' => $data['specialties'],
                'client_profile_id' => $clientProfile->id
            ]);

            $this->syncLanguages($freelanceJobVacancy, $data['languages'] ?? []);

            GenerateFreelanceJobVacancyEmbeddingJob::dispatchDebounced($freelanceJobVacancy->id);

            return $freelanceJobVacancy;
        });

        return $this->findWithViewerState($freelanceJobVacancy->id);
    }

    public function show(array $data)
    {
        return $this->findWithViewerState($data['id']);
    }

    public function update(array $data)
    {
        $freelanceJobVacancy = $this->findClientVacancy($data['id']);

        if (!$freelanceJobVacancy->acceptsProposals()) {
            throw new ApiException("Only open freelance job vacancies can be edited!", 422);
        }

        $data = Arr::except($data, ['id']);

        DB::transaction(function () use ($data, $freelanceJobVacancy) {

            $freelanceJobVacancy->update(Arr::only($data, [
                'title',
                'description',
                'job_type',
                'budget',
                'seniority_level',
                'specialties',
            ]));

            // O array de linguagens, quando enviado, representa o estado final da vaga
            if (array_key_exists('languages', $data)) {
                $this->syncLanguages($freelanceJobVacancy, $data['languages']);
            }

            GenerateFreelanceJobVacancyEmbeddingJob::dispatchDebounced($freelanceJobVacancy->id);
        });

        return $this->findWithViewerState($freelanceJobVacancy->id);
    }

    /**
     * Só o freela aberto pode ser removido: depois do contrato fechado, ele guarda o
     * histórico do trabalho no feed
     */
    public function destroy(array $data)
    {
        $freelanceJobVacancy = $this->findClientVacancy($data['id']);

        if (!$freelanceJobVacancy->acceptsProposals()) {
            throw new ApiException("Only open freelance job vacancies can be removed!", 422);
        }

        return DB::transaction(function () use ($freelanceJobVacancy) {

            $freelanceJobVacancy->languages()->detach();
            $freelanceJobVacancy->delete();

            return $freelanceJobVacancy;
        });
    }

    /**
     * O cliente encerra o contrato em andamento. O feed continua disponível para consulta
     */
    public function complete(array $data)
    {
        $freelanceJobVacancy = $this->findClientVacancy($data['id']);

        if ($freelanceJobVacancy->status !== FreelanceJobVacancyStatusEnum::IN_PROGRESS) {
            throw new ApiException("Only freelance job vacancies in progress can be completed!", 422);
        }

        DB::transaction(function () use ($freelanceJobVacancy) {

            $freelanceJobVacancy->update([
                'status' => FreelanceJobVacancyStatusEnum::COMPLETED,
                'completed_at' => now()
            ]);

            $freelanceJobVacancy->acceptedProposal?->devProfile?->notifications()->create([
                'type' => 'freelance_job_vacancy_completed',
                'title' => 'Freela concluído',
                'message' => "O cliente marcou o freela {$freelanceJobVacancy->title} como concluído. Obrigado pelo trabalho!",
                'link' => $this->freelanceJobVacancyLink($freelanceJobVacancy)
            ]);
        });

        return $this->findWithViewerState($freelanceJobVacancy->id);
    }

    /**
     * Freelas abertos, que ainda recebem orçamento, do mais novo para o mais antigo
     * e com o estado de quem está vendo. A vitrine e a busca global partem daqui
     */
    public function openVacanciesQuery(?string $search = null): Builder
    {
        return $this->query()
            ->where('status', FreelanceJobVacancyStatusEnum::OPEN)
            ->when($search, fn(Builder $query, $search) => $this->applySearch($query, $search))
            ->tap(fn(Builder $query) => $this->withViewerState($query))
            ->latest();
    }

    /**
     * Consulta base com o que o resource da vaga sempre exibe
     */
    private function query(): Builder
    {
        return FreelanceJobVacancy::query()->with(['languages', 'clientProfile.user']);
    }

    /**
     * Uma vaga com tudo o que cabe a quem está vendo: o contrato (o resource só mostra
     * para as duas pontas dele) e o estado de cada lado
     */
    private function findWithViewerState(string $freelanceJobVacancyId): FreelanceJobVacancy
    {
        return $this->query()
            ->with('acceptedProposal.devProfile.user')
            ->tap(fn(Builder $query) => $this->withViewerState($query))
            ->findOrFail($freelanceJobVacancyId);
    }

    /**
     * O que cada lado vê além da vaga: o cliente, quantos orçamentos ela recebeu (o
     * resource só mostra para o dono); o dev, o orçamento que já enviou e se foi convidado
     */
    private function withViewerState(Builder $query): Builder
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($authUser->hasRole('client')) {
            return $query->withCount('proposals');
        }

        $devProfileId = $authUser->hasRole('dev') ? $authUser->dev_profile?->id : null;

        if (!$devProfileId) {
            return $query;
        }

        return $query
            ->with(['proposals' => fn($q) => $q->where('dev_profile_id', $devProfileId)])
            ->withExists(['invitations as is_invited' => fn(Builder $q) => $q->where('dev_profile_id', $devProfileId)]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('job_type', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%")
                ->orWhereHas('languages', function (Builder $q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
        });
    }

    /**
     * Substitui os vínculos de linguagem da vaga pelo array recebido, mantendo o
     * `language_level` de cada uma na pivot.
     */
    protected function syncLanguages(FreelanceJobVacancy $freelanceJobVacancy, array $languages): void
    {
        $freelanceJobVacancy->languages()->sync(
            collect($languages)->mapWithKeys(fn(array $language) => [
                $language['language_id'] => ['language_level' => $language['language_level']]
            ])->all()
        );
    }
}
