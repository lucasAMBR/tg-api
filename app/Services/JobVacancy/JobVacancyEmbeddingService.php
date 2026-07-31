<?php 

namespace App\Services\JobVacancy;

use App\Models\JobVacancy;
use App\Models\JobVacancyEmbedding;
use App\Services\Embeddings\EmbeddingService;

class JobVacancyEmbeddingService {

    public function __construct(protected EmbeddingService $embeddingService) {}

    public function createJobVacancyEmbedding(JobVacancy $jobVacancy)
    {
        $context = $this->constructJobVacancyContext($jobVacancy);
        $embedding = $this->embeddingService->generate($context);

        $jobEmbedding = JobVacancyEmbedding::updateOrCreate(
            ['job_vacancy_id' => $jobVacancy->id],
            ['embedding' => $embedding]
        );

        return $jobEmbedding;
    }

    private function constructJobVacancyContext(JobVacancy $jobVacancy): string
    {
        $finalText = '';

        $finalText .= $this->constructJobVacancyBasicInformation($jobVacancy);
        $finalText .= $this->constructJobVacancyBenefits($jobVacancy);

        return $finalText;
    }

    private function constructJobVacancyBasicInformation(JobVacancy $jobVacancy): string
    {
        $basicData = "[Informações da Vaga] \n";
        $basicData .= "Título da Vaga: {$jobVacancy->title}\n";
        
        if (!empty($jobVacancy->specialties)) {
            $specialtiesText = implode(', ', $jobVacancy->specialties);
            $basicData .= "Especialidades: {$specialtiesText}\n";
        }

        if ($jobVacancy->seniority_level) {
            $basicData .= "Nível de Senioridade: {$jobVacancy->seniority_level}\n";
        }

        if ($jobVacancy->contract_type) {
            $basicData .= "Tipo de Contrato: {$jobVacancy->contract_type}\n";
        }

        if ($jobVacancy->employment_type) {
            $basicData .= "Modelo de Trabalho: {$jobVacancy->employment_type}\n";
        }
        
        if ($jobVacancy->estimated_salary) {
            $basicData .= "Salário Estimado: R$ " . number_format($jobVacancy->estimated_salary, 2, ',', '.') . "\n";
        }

        if ($jobVacancy->description) {
            $basicData .= "\nDescrição da Vaga:\n{$jobVacancy->description}\n\n";
        }

        return $basicData;
    }

    private function constructJobVacancyBenefits(JobVacancy $jobVacancy): string
    {
        $benefits = $jobVacancy->benefits;

        if (empty($benefits) || !is_array($benefits)) {
            return '';
        }

        $benefitsText = "[Benefícios] \n";
        $benefitsText .= "A vaga oferece os seguintes benefícios: " . implode(', ', $benefits) . ".\n\n";

        return $benefitsText;
    }

}