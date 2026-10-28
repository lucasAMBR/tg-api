<?php

namespace App\Jobs;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Services\Translation\TranslationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];
    
    /**
     * Create a new job instance.
     */
    public function __construct(
        public Translatable $model
    ){
        $this->onQueue('translations');
    }

    /**
     * Execute the job.
     */
    public function handle(TranslationService $translationService): void
    {
        $this->model->updateTranslationStatus(TranslationStatusEnum::TRANSLATING->value);

        try {
            $contentToTranslate = $this->model->getTranslatableContent();

            $translatedContent = $translationService->translateBatch($contentToTranslate);

            $this->model->applyTranslation($translatedContent);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->model->updateTranslationStatus(TranslationStatusEnum::ERROR->value);

        Log::channel('translations')->error('Error translating content', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
