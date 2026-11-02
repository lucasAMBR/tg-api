<?php

namespace App\Jobs;

use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\User;
use App\Services\ProfileScore\ProfileScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateProfileScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O perfil pode ter sido excluído antes de o job rodar
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public DevProfile|CompanyProfile|ClientProfile $profile,
    )
    {
        $this->onQueue('profile-score');
        // Quase sempre quem dispara está numa transação, e o cálculo precisa ver o que ela gravou
        $this->afterCommit();
    }

    /**
     * Recalcula os perfis do usuário. Normalmente é um só, mas o admin pode ter os três,
     * e as interações sociais contam para todos eles
     */
    public static function dispatchForUser(User|string|null $user): void
    {
        $user = is_string($user) ? User::find($user) : $user;

        if (!$user) {
            return;
        }

        $user->loadMissing(['dev_profile', 'company_profile', 'client_profile']);

        foreach ([$user->dev_profile, $user->company_profile, $user->client_profile] as $profile) {
            if ($profile) {
                self::dispatch($profile);
            }
        }
    }

    /**
     * Execute the job.
     */
    public function handle(ProfileScoreService $profileScoreService): void
    {
        $profileScoreService->recalculate($this->profile);
    }
}
