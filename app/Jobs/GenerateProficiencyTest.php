<?php

namespace App\Jobs;

use App\Models\DevProfile;
use App\Models\ProficiencyTest;
use App\Services\ProficiencyTest\ProficiencyTestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProficiencyTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DevProfile $devProfile,
        public ProficiencyTest $proficiencyTest,
        public array $data,
    )
    {
        $this->onQueue('proficiency-tests');
    }

    /**
     * Execute the job.
     */
    public function handle(ProficiencyTestService $proficiencyTestService): void
    {
        $proficiencyTestService->generateProficiencyTest(
            $this->devProfile,
            $this->proficiencyTest,
            $this->data,
        );
    }
}
