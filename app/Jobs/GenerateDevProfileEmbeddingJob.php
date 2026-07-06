<?php

namespace App\Jobs;

use App\Models\DevProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

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
    {}

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
