<?php

namespace App\Jobs;

use App\Models\FreelanceJobVacancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateFreelanceJobVacancyEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $freelanceJobVacancyId,
        public string $token,
    )
    {
        $this->queue = "freelance-vacancy-embedding";
    }

    /**
     * Dispatch the embedding regeneration with a debounce, so multiple changes
     * to a freelance job vacancy within a short window only trigger a single embedding.
     */
    public static function dispatchDebounced(string $freelanceJobVacancyId): void
    {
        $token = (string) Str::uuid();

        Cache::put("freelance_job_vacancy_embedding_token:{$freelanceJobVacancyId}", $token, now()->addMinutes(10));

        $delay = (int) config('app.services.embedding.debounce_seconds', 60);

        self::dispatch($freelanceJobVacancyId, $token)->delay(now()->addSeconds($delay));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentToken = Cache::get("freelance_job_vacancy_embedding_token:{$this->freelanceJobVacancyId}");

        if ($currentToken !== $this->token) {
            return;
        }

        $freelanceJobVacancy = FreelanceJobVacancy::with('languages')->find($this->freelanceJobVacancyId);

        if ($freelanceJobVacancy) {
            app(\App\Services\FreelanceJobVacancy\FreelanceJobVacancyEmbeddingService::class)
                ->createFreelanceJobVacancyEmbedding($freelanceJobVacancy);
        }

        Cache::forget("freelance_job_vacancy_embedding_token:{$this->freelanceJobVacancyId}");
    }
}
