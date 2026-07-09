<?php

namespace App\Jobs;

use App\Models\ProficiencyTest;
use App\Services\ProficiencyTest\ProficiencyTestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateProficiencyTestScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProficiencyTest $proficiencyTest,
    )
    {
        $this->onQueue('tests-pontuation-calc');
    }

    /**
     * Execute the job.
     */
    public function handle(ProficiencyTestService $proficiencyTestService): void
    {
        $proficiencyTestService->calculateProficiencyTestScore($this->proficiencyTest);
    }
}
