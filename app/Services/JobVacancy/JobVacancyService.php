<?php

namespace App\Services\JobVacancy;

use App\Models\JobVacancy;
use Illuminate\Database\Eloquent\Builder;
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
                // Colocar busca pelo nivei da linguagem
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

        return DB::transaction(function() use ($data) {

            $jobVacancy = JobVacancy::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'employment_type' => $data['employment_type'],
                'benefits' => $data['benefits'],
                'estimated_salary' => $data['estimated_salary'],
                'contract_type' => $data['contract_type'],
                'seniority_level' => $data['seniority_level'],
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

            // Retorna ja com as relações carregadas
            return $jobVacancy->load('languages', 'softSkill');

        });

    }

    public function show(JobVacancy $jobVacancy) {

        // load() porque ja carrega a relação sem precisar consultar novamente o banco
        return $jobVacancy->load(['softSkill', 'languages']);

    }

    public function update() {
        //
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
