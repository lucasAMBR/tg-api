<?php

use App\Enums\SelectionProcessStageEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Etapas antigas que foram unificadas em uma única etapa de entrevista
     */
    private const LEGACY_INTERVIEW_STEPS = [
        'hr_interview' => SelectionProcessStageEnum::INTERVIEW,
        'technical_interview' => SelectionProcessStageEnum::INTERVIEW,
        'behavioral_interview' => SelectionProcessStageEnum::INTERVIEW,
        'cultural_fit_interview' => SelectionProcessStageEnum::INTERVIEW,
        'team_interview' => SelectionProcessStageEnum::INTERVIEW,
        'final_interview' => SelectionProcessStageEnum::INTERVIEW,
        'awaiting_hr_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
        'awaiting_technical_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
        'awaiting_behavioral_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
        'awaiting_cultural_fit_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
        'awaiting_team_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
        'awaiting_final_interview' => SelectionProcessStageEnum::AWAITING_INTERVIEW,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $jobVacancyIds = DB::table('job_vacancy_process_steps')
            ->whereIn('step', array_keys(self::LEGACY_INTERVIEW_STEPS))
            ->distinct()
            ->pluck('job_vacancy_id');

        foreach ($jobVacancyIds as $jobVacancyId) {
            $this->normalizeJobVacancySteps($jobVacancyId);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    /**
     * Converte as etapas antigas de entrevista, remove as duplicadas geradas
     * pela unificação e reordena as etapas restantes
     */
    private function normalizeJobVacancySteps(string $jobVacancyId): void
    {
        $steps = DB::table('job_vacancy_process_steps')
            ->where('job_vacancy_id', $jobVacancyId)
            ->orderBy('order')
            ->get();

        $keptSteps = [];
        $duplicatedIds = [];

        foreach ($steps as $step) {
            $normalized = self::LEGACY_INTERVIEW_STEPS[$step->step]->value ?? $step->step;

            if (in_array($normalized, $keptSteps, true)) {
                $duplicatedIds[] = $step->id;
                continue;
            }

            $keptSteps[] = $normalized;

            DB::table('job_vacancy_process_steps')
                ->where('id', $step->id)
                ->update([
                    'step' => $normalized,
                    'order' => count($keptSteps),
                ]);
        }

        if ($duplicatedIds !== []) {
            DB::table('job_vacancy_process_steps')
                ->whereIn('id', $duplicatedIds)
                ->delete();
        }
    }
};
