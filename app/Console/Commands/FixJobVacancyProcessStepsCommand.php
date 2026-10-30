<?php

namespace App\Console\Commands;

use App\Models\JobVacancy;
use App\Models\JobVacancyProcessStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixJobVacancyProcessStepsCommand extends Command
{
    protected $signature = 'job-vacancies:fix-process-steps
                            {--dry-run : Apenas lista as vagas que seriam corrigidas, sem alterar nada}';

    protected $description = 'Coloca a triagem de currículos como primeira etapa das vagas criadas sem ela e reordena as demais etapas';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixed = 0;

        JobVacancy::query()
            ->with('processSteps')
            ->chunkById(100, function ($jobVacancies) use ($dryRun, &$fixed) {
                foreach ($jobVacancies as $jobVacancy) {
                    $currentSteps = $jobVacancy->processSteps
                        ->map(fn (JobVacancyProcessStep $processStep) => [
                            'step' => $processStep->step,
                            'order' => $processStep->order,
                        ])
                        ->all();

                    $expectedSteps = JobVacancyProcessStep::withResumeScreeningFirst($currentSteps);

                    if ($currentSteps === $expectedSteps) {
                        continue;
                    }

                    $fixed++;

                    $this->line(sprintf(
                        '<fg=yellow>%s</> Vaga %s (%s): %s -> %s',
                        $dryRun ? '[dry-run]' : '✓',
                        $jobVacancy->id,
                        $jobVacancy->title,
                        $this->describeSteps($currentSteps),
                        $this->describeSteps($expectedSteps)
                    ));

                    if ($dryRun) {
                        continue;
                    }

                    DB::transaction(function () use ($jobVacancy, $expectedSteps) {
                        $jobVacancy->processSteps()->delete();
                        $jobVacancy->processSteps()->createMany($expectedSteps);
                    });
                }
            });

        if ($fixed === 0) {
            $this->info('Todas as vagas já seguem o padrão.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($dryRun ? "Vagas que seriam corrigidas: {$fixed}" : "Vagas corrigidas: {$fixed}");

        return self::SUCCESS;
    }

    /**
     * @param list<array{step: string, order: int}> $steps
     */
    private function describeSteps(array $steps): string
    {
        return $steps === [] ? '(nenhuma etapa)' : implode(', ', array_column($steps, 'step'));
    }
}
