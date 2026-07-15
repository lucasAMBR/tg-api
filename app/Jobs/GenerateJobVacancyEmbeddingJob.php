<?php

namespace App\Jobs;

use App\Models\JobVacancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateJobVacancyEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $jobVacancyId,
        public string $token,
    )
    {
        $this->queue = "vacancy-embedding"; 
    }

    public static function dispatchDebounced(string $jobVacancyId): void
    {
        $token = (string) Str::uuid();

        Cache::put("job_vacancy_embedding_token:{$jobVacancyId}", $token, now()->addMinutes(10));

        $delay = (int) config('app.services.embedding.debounce_seconds', 60);

        self::dispatch($jobVacancyId, $token)->delay(now()->addSeconds($delay));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentToken = Cache::get("job_vacancy_embedding_token:{$this->jobVacancyId}");

        if ($currentToken !== $this->token) {
            return;
        }

        $jobVacancy = JobVacancy::find($this->jobVacancyId);

        if ($jobVacancy) {
            app(\App\Services\JobVacancy\JobVacancyEmbeddingService::class)->createJobVacancyEmbedding($jobVacancy);
        }

        Cache::forget("job_vacancy_embedding_token:{$this->jobVacancyId}");
    }
}