<?php

namespace App\Jobs;

use App\Enums\ProficiencyTestStatusEnum;
use App\Models\DevProfile;
use App\Models\ProficiencyTest;
use App\Services\ProficiencyTest\ProficiencyTestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateProficiencyTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

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
        $this->devProfile->notifications()->create([
            'type' => 'proficiency_test_generation_start',
            'title' => 'Teste de proficiência',
            'message' => 'Estamos gerando o seu teste de proficiência, aguarde alguns minutos e entraremos em contato novamente!'
        ]);

        $proficiencyTestService->generateProficiencyTest(
            $this->devProfile,
            $this->proficiencyTest,
            $this->data,
        );

        $this->devProfile->notifications()->create([
            'type' => 'proficiency_test_generated',
            'title' => 'Teste de proficiência',
            'message' => 'Seu teste de proficência esta pronto! Clique nessa notificação ou vá para a aba de testes de proficiência no seu perfil para realiza-lo!',
            'link' => env('APP_URL') . "/proficiency-teste/{$this->proficiencyTest->id}"
        ]);
    }

    public function failed(Throwable $e)
    {
        $this->proficiencyTest->update([
            'status' => ProficiencyTestStatusEnum::GENERATION_FAILED->value
        ]);

        $this->devProfile->notifications()->create([
            'type' => 'proficiency_test_generation_start',
            'title' => 'Teste de proficiência',
            'message' => 'Houve um erro ao gerar o seu teste de proficiência, desculpas pelo imprevisto, pedimos que solicite o teste novamente mais tarde!'
        ]);

        Log::channel('proficiency_test')->error(
            'Proficiency test generation failed', [
            'proficiency_test_id' => $this->proficiencyTest->id,
            'dev_profile_id'      => $this->devProfile->id,
            'exception'           => $e->getMessage(),
        ]);
    }
}
