<?php

namespace App\Services\FreelanceJobVacancy;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\FreelanceJobVacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FreelanceJobVacancyService
{
    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;
        $jobType = $data['job_type'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $onlyMine = $data['only_mine'] ?? false;

        return FreelanceJobVacancy::query()
            ->with('languages', 'clientProfile')
            ->when($search, function (Builder $query, $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('job_type', 'ILIKE', "%{$search}%")
                        ->orWhere('seniority_level', 'ILIKE', "%{$search}%")
                        ->orWhereHas('languages', function (Builder $q) use ($search) {
                            $q->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->when($jobType, fn(Builder $query, $jobType) => $query->where('job_type', $jobType))
            ->when($seniorityLevel, fn(Builder $query, $seniorityLevel) => $query->where('seniority_level', $seniorityLevel))
            ->when($onlyMine, function (Builder $query) {
                $query->where('client_profile_id', $this->resolveClientProfile()->id);
            })
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );
    }

    public function store(array $data)
    {
        $clientProfile = $this->resolveClientProfile();

        return DB::transaction(function () use ($data, $clientProfile) {

            $languages = $data['languages'] ?? [];

            $freelanceJobVacancy = FreelanceJobVacancy::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'job_type' => $data['job_type'],
                'salary_type' => $data['salary_type'],
                'estimated_salary' => $data['estimated_salary'],
                'seniority_level' => $data['seniority_level'],
                'specialties' => $data['specialties'],
                'client_profile_id' => $clientProfile->id
            ]);

            $this->syncLanguages($freelanceJobVacancy, $languages);

            // Retorna ja com as relações carregadas
            return $freelanceJobVacancy->load('languages', 'clientProfile');
        });
    }

    public function show(array $data)
    {
        $freelanceJobVacancy = FreelanceJobVacancy::findOrFail($data['id']);

        // load() porque ja carrega a relação sem precisar consultar novamente o banco
        return $freelanceJobVacancy->load(['languages', 'clientProfile']);
    }

    public function update(array $data)
    {
        $freelanceJobVacancy = FreelanceJobVacancy::findOrFail($data['id']);

        $this->authorizeOwnership($freelanceJobVacancy);

        $data = Arr::except($data, ['id']);

        return DB::transaction(function () use ($data, $freelanceJobVacancy) {

            $freelanceJobVacancy->update(Arr::only($data, [
                'title',
                'description',
                'job_type',
                'salary_type',
                'estimated_salary',
                'seniority_level',
                'specialties',
            ]));

            // O array de linguagens, quando enviado, representa o estado final da vaga
            if (array_key_exists('languages', $data)) {
                $this->syncLanguages($freelanceJobVacancy, $data['languages']);
            }

            return $freelanceJobVacancy->fresh(['languages', 'clientProfile']);
        });
    }

    public function destroy(array $data)
    {
        $freelanceJobVacancy = FreelanceJobVacancy::findOrFail($data['id']);

        $this->authorizeOwnership($freelanceJobVacancy);

        return DB::transaction(function () use ($freelanceJobVacancy) {

            $freelanceJobVacancy->languages()->detach();
            $freelanceJobVacancy->delete();

            return $freelanceJobVacancy;
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

    /**
     * Só o cliente dono da vaga pode alterá-la ou removê-la.
     */
    protected function authorizeOwnership(FreelanceJobVacancy $freelanceJobVacancy): void
    {
        $clientProfile = $this->resolveClientProfile();

        if ($freelanceJobVacancy->client_profile_id !== $clientProfile->id) {
            throw new ApiException("You can only manage your own job vacancies!", 403);
        }
    }

    /**
     * Garante que o usuário autenticado é um cliente com perfil ativo.
     */
    protected function resolveClientProfile()
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if (!$authUser->hasRole('client')) {
            throw new ApiException("You must be a client for manage a freelance job vacancy", 403);
        }

        $clientProfile = ProfileHelper::getUserProfileByRole($authUser);

        if (!$clientProfile) {
            throw new ApiException("You don't have an active profile!");
        }

        return $clientProfile;
    }
}
