<?php

namespace App\Services\JobVacancy;

use App\Models\JobVacancy;
use Illuminate\Support\Facades\DB;

class JobVacancyService {

    public function index() {
        //
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

    public function show() {
        //
    }

    public function update() {
        //
    }

    public function delete() {
        //
    }
}
