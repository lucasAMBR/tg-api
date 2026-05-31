<?php

namespace App\Services\JobVacancy;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\JobVacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobVacancyService {

    public function index(array $data) {

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;

        $jobVacancy = JobVacancy::query()->with('softSkill', 'languages')
        ->when($search, function(Builder $query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('contract_type', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%")
                // Colocar busca pelo nivel da linguagem
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
                
            // Retorna ja com as relações carregadas
            return $jobVacancy->load('languages', 'softSkill', 'desirableLanguage', 'companyProfile');

        });

    }

    public function show(JobVacancy $jobVacancy) {

        // load() porque ja carrega a relação sem precisar consultar novamente o banco
        return $jobVacancy->load(['softSkill', 'languages']);

    }

    public function update(array $data, JobVacancy $jobVacancy) {

        return DB::transaction(function() use ($data, $jobVacancy) {

            // VAGA
            $jobVacancy->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'employment_type' => $data['employment_type'],
                'benefits' => $data['benefits'],
                'estimated_salary' => $data['estimated_salary'],
                'contract_type' => $data['contract_type'],
                'seniority_level' => $data['seniority_level'],
            ]);

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

            return $jobVacancy->fresh(['softSkill', 'languages']);

        });
    }

    public function destroy(JobVacancy $jobVacancy) {

        return DB::transaction(function() use($jobVacancy) {

            $jobVacancy->delete();
            $jobVacancy->languages()->detach();
            $jobVacancy->softSkill()->detach();

            return $jobVacancy;

        });

    }
}
