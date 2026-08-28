<?php

namespace App\Services\FreelanceJobVacancy;

use App\Enums\DevSpecialtyEnum;
use App\Enums\HardSkillLevelsEnum;
use App\Models\FreelanceJobVacancy;
use App\Models\FreelanceJobVacancyEmbedding;
use App\Services\Embeddings\EmbeddingService;

class FreelanceJobVacancyEmbeddingService
{
    public function __construct(protected EmbeddingService $embeddingService) {}

    public function createFreelanceJobVacancyEmbedding(FreelanceJobVacancy $freelanceJobVacancy)
    {
        $context = $this->constructFreelanceJobVacancyContext($freelanceJobVacancy);

        $vector = $this->embeddingService->generate($context);

        $freelanceJobVacancyEmbedding = FreelanceJobVacancyEmbedding::updateOrCreate(
            ['freelance_job_vacancy_id' => $freelanceJobVacancy->id],
            ['embedding' => $vector]
        );

        return $freelanceJobVacancyEmbedding;
    }

    private function constructFreelanceJobVacancyContext(FreelanceJobVacancy $freelanceJobVacancy): string
    {
        $finalText = '';

        $finalText .= $this->constructBasicInformation($freelanceJobVacancy);
        $finalText .= $this->constructLanguages($freelanceJobVacancy);

        return $finalText;
    }

    private function constructBasicInformation(FreelanceJobVacancy $freelanceJobVacancy): string
    {
        $basicData = "[Informações da Vaga Freelance] \n";
        $basicData .= "Título da Vaga: {$freelanceJobVacancy->title}\n";

        if (!empty($freelanceJobVacancy->specialties)) {
            $specialtiesText = collect($freelanceJobVacancy->specialties)
                ->map(fn(string $specialty) => DevSpecialtyEnum::tryFrom($specialty)?->labelPt() ?? $specialty)
                ->implode(', ');

            $basicData .= "Especialidades: {$specialtiesText}\n";
        }

        if ($freelanceJobVacancy->seniority_level) {
            $basicData .= "Nível de Senioridade: {$freelanceJobVacancy->seniority_level->labelPt()}\n";
        }

        if ($freelanceJobVacancy->job_type) {
            $basicData .= "Tipo de Trabalho: {$freelanceJobVacancy->job_type->labelPt()}\n";
        }

        if ($freelanceJobVacancy->estimated_salary) {
            $salary = "R$ " . number_format((float) $freelanceJobVacancy->estimated_salary, 2, ',', '.');

            $basicData .= $freelanceJobVacancy->salary_type
                ? "Remuneração Estimada: {$salary} ({$freelanceJobVacancy->salary_type->labelPt()})\n"
                : "Remuneração Estimada: {$salary}\n";
        }

        if ($freelanceJobVacancy->description) {
            $basicData .= "\nDescrição da Vaga:\n{$freelanceJobVacancy->description}\n\n";
        }

        return $basicData;
    }

    private function constructLanguages(FreelanceJobVacancy $freelanceJobVacancy): string
    {
        $languages = $freelanceJobVacancy->languages;

        if ($languages->isEmpty()) {
            return '';
        }

        $languagesText = "[Stack Requisitada] \n";
        $languagesText .= "A vaga exige as seguintes tecnologias: \n";

        foreach ($languages as $language) {
            $languageLevel = $language->pivot->language_level;

            $level = $languageLevel instanceof HardSkillLevelsEnum
                ? $languageLevel
                : HardSkillLevelsEnum::tryFrom((string) $languageLevel);

            $languagesText .= $level
                ? "{$language->name} ({$level->labelPt()}).\n"
                : "{$language->name}.\n";
        }

        $languagesText .= "\n";

        return $languagesText;
    }
}
