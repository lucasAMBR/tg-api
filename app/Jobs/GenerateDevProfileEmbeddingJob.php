<?php

namespace App\Jobs;

use App\Models\DevProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateDevProfileEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $devProfileId,
        public string $token,
    )
    {
        $this->queue = "profile-embedding";
    }

    /**
     * Dispatch the embedding regeneration with a debounce, so multiple changes
     * to a dev profile within a short window only trigger a single embedding.
     */
    public static function dispatchDebounced(string $devProfileId): void
    {
        $token = (string) Str::uuid();

        Cache::put("profile_embedding_token:{$devProfileId}", $token, now()->addMinutes(10));

        $delay = (int) config('app.services.embedding.debounce_seconds', 60);

        self::dispatch($devProfileId, $token)->delay(now()->addSeconds($delay));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentToken = Cache::get("profile_embedding_token:{$this->devProfileId}");

        if ($currentToken !== $this->token) {
            return;
        }

        $devProfile = DevProfile::find($this->devProfileId);

        app(\App\Services\Profiles\ProfileEmbeddingService::class)->createDevProfileEmbedding($devProfile);

        Cache::forget("profile_embedding_token:{$this->devProfileId}");
    }
}
