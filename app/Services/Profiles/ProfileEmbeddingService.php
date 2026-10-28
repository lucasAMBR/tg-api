<?php

namespace App\Services\Profiles;

use App\Enums\DegreeLevelEnum;
use App\Enums\DevSpecialtyEnum;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SeniorityLevelEnum;
use App\Enums\TranslationStatusEnum;
use App\Models\DevProfile;
use App\Models\DevProfileEmbedding;
use App\Models\ProjectHistory;
use App\Services\Embeddings\EmbeddingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProfileEmbeddingService
{
    public function __construct(protected EmbeddingService $embeddingService){}

    public function createDevProfileEmbedding(DevProfile $devProfile)
    {
        $context = $this->constructContext($devProfile);

        $vector = $this->embeddingService->generate($context);

        $profileEmbedding = DevProfileEmbedding::updateOrCreate(
            ['dev_profile_id' => $devProfile->id],
            ['embedding' => $vector]
        );

        return $profileEmbedding;
    }

    public function constructContext(DevProfile $devProfile): string
    {
        $finalText = '';

        $finalText .= $this->constructBasicInformation($devProfile);
        $finalText .= $this->constructExperience($devProfile);
        $finalText .= $this->constructAcademicBackground($devProfile);
        $finalText .= $this->constructHardSkills($devProfile);
        $finalText .= $this->constructSoftSkills($devProfile);
        $finalText .= $this->constructAdditionalCourses($devProfile);
        $finalText .= $this->constructProjects($devProfile);

        return $finalText;
    }

    private function constructBasicInformation(DevProfile $devProfile): string
    {
        $specialty = DevSpecialtyEnum::from($devProfile->specialty)->labelPt();
        $seniority = SeniorityLevelEnum::from($devProfile->seniority_level)->labelPt();

        $bio = $this->translatedField($devProfile, 'bio');

        $basicData = "[Perfil e Senioridade] \n";

        $basicData .= "Desenvolvedor {$specialty} que se autoavalia como {$seniority}.\n";
        $basicData .= $bio ? " {$bio}\n\n" : '';

        return $basicData;
    }

    private function constructExperience(DevProfile $devProfile): string
    {
        $employmentHistory = $devProfile->employment_histories;

        if ($employmentHistory->isEmpty()) {
            return '';
        }
        
        $experience = "[Experiência Profissional] \n";

        foreach ($employmentHistory as $employment) {
            $seniority = SeniorityLevelEnum::from($employment->seniority_level)->labelPt();
            $positionName = $this->translatedField($employment, 'position_name');
            $actuationDetails = $this->translatedField($employment, 'actuation_details');

            if($employment->start_date && $employment->end_date && !$employment->is_current) {
                $experience .= "trabalhou em {$employment->company_name} no cargo de {$positionName} ({$seniority}) por {$this->calculateJobDuration($employment->start_date, $employment->end_date)}.";
                $experience .= "Detalhes: {$actuationDetails}.\n";
            }else{
                $experience .= "Atualmente trabalha em {$employment->company_name} no cargo de {$positionName} ({$seniority}) desde {$this->formatDate($employment->start_date)}. \n";
                $experience .= "Detalhes: {$actuationDetails}\n";
            }
        }

        $experience .= "\n";

        return $experience;
    }

    private function constructAcademicBackground(DevProfile $devProfile): string
    {
        $academicBackground = $devProfile->academic_backgrounds;

        if ($academicBackground->isEmpty()) {
            return '';
        }

        $academic = "[Formação Acadêmica] \n";
        
        foreach ($academicBackground as $background) {
            $degreeLevel = DegreeLevelEnum::from($background->degree_level)->labelPt();
            $degree = $this->translatedField($background, 'degree');

            $academic .= "Formou-se em {$degree} ({$degreeLevel}) na {$background->institution}.\n";
        }

        $academic .= "\n";

        return $academic;
    }

    private function constructHardSkills(DevProfile $devProfile): string
    {
        $hardSkills = $devProfile->hard_skills;

        if ($hardSkills->isEmpty()) {
            return '';
        }

        $hardSkillsText = "[Habilidades Técnicas] \n";

        $hardSkillsText .= "Seu nível de conhecimento em cada habilidade técnica é: \n";

        foreach ($hardSkills as $hardSkill) {
            $level = HardSkillLevelsEnum::from($hardSkill->skill_level)->labelPt();
            
            $hardSkillsText .= "{$hardSkill->language->name} ({$level}).\n";
        }

        $hardSkillsText .= "\n";

        return $hardSkillsText;
    }

    private function constructSoftSkills(DevProfile $devProfile): string
    {
        $softSkills = $this->topSoftSkillsByEvaluationWeight($devProfile->dev_soft_skills);

        if ($softSkills->isEmpty()) {
            return '';
        }

        $softSkillsText = "[Habilidades de Soft Skills] \n";

        $softSkillsText .= "Suas melhores soft skills são avaliadas em: \n";
        
        foreach ($softSkills as $softSkill) {
            $softSkillsText .= "{$softSkill->soft_skill->name_pt}: {$softSkill->soft_skill_level_response->title_pt}, {$softSkill->soft_skill_level_response->description_pt}\n";
        }

        $softSkillsText .= "\n";

        return $softSkillsText;
    }

    private function topSoftSkillsByEvaluationWeight(Collection $softSkills, int $limit = 3)
    {
        return $softSkills
            ->sortByDesc(fn ($softSkill) => $softSkill->soft_skill_level_response->evaluation_weight)
            ->take($limit)
            ->values();
    }

    private function constructAdditionalCourses(DevProfile $devProfile): string
    {
        $additionalCourses = $devProfile->additional_courses;

        if ($additionalCourses->isEmpty()) {
            return '';
        }
        
        $additionalCoursesText = "[Certificados] \n";

        $additionalCoursesText .= "Possui os seguintes certificados: \n";

        foreach ($additionalCourses as $additionalCourse) {
            $additionalCoursesText .= "{$additionalCourse->name} emitido por ({$additionalCourse->provider}).\n";
        }

        $additionalCoursesText .= "\n";

        return $additionalCoursesText;
    }

    private function constructProjects(DevProfile $devProfile): string
    {
        $projects = $devProfile->project_histories;

        if ($projects->isEmpty()) {
            return '';
        }

        $projectsText = "[Projetos] \n";

        $projectsText .= "Participou dos seguintes projetos: \n";

        foreach ($projects as $project) {
            $title = $this->translatedField($project, 'title');
            $description = $this->translatedField($project, 'description');

            $projectsText .= "{$title}: {$description} \n";
            $projectsText .= "Tecnologias utilizadas: {$this->projectLanguagesToText($project)}.\n";
        }

        $projectsText .= "\n";

        return $projectsText;
    }

    private function calculateJobDuration(Carbon $startDate, Carbon $endDate): string
    {
        $months = $startDate->diffInMonths($endDate);

        if ($months < 12) {
            return "{$months} meses";
        }

        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        return "{$years} anos e {$remainingMonths} meses";
    }

    private function formatDate(Carbon $date): string
    {
        return $date->format('d/m/Y');
    }

    private function projectLanguagesToText(ProjectHistory $project): string
    {
        return $project->languages->pluck('name')->implode(', ');
    }

    private function translatedField(object $model, string $field): string
    {
        if ($model->translation_status === TranslationStatusEnum::TRANSLATED->value) {
            return $model->{"{$field}_pt"} ?? $model->{$field} ?? '';
        }

        return $model->{$field} ?? '';
    }
}