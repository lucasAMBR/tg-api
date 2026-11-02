<?php

namespace App\Console\Commands;

use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Services\ProfileScore\ProfileScoreService;
use Illuminate\Console\Command;

/**
 * Recalcula o score de todos os perfis a partir do que está no banco. O score já se recalcula
 * a cada evento que dá ou tira pontos, então isto serve para a primeira carga depois de mudar
 * a regra, para dados criados por fora dos services (os seeders) e para acertar perfis que
 * ficaram para trás, como os que perderam favoritos de uma conta excluída.
 */
class RecalculateProfileScoresCommand extends Command
{
    protected $signature = 'profile-score:recalculate';

    protected $description = 'Recalcula o score de credibilidade de todos os perfis de dev, empresa e cliente';

    public function handle(ProfileScoreService $profileScoreService): int
    {
        $profileModels = [
            'dev' => DevProfile::class,
            'company' => CompanyProfile::class,
            'client' => ClientProfile::class,
        ];

        $rows = [];

        foreach ($profileModels as $type => $class) {
            $total = 0;
            $changed = 0;

            $class::query()->lazyById(100)->each(function (DevProfile|CompanyProfile|ClientProfile $profile) use ($profileScoreService, &$total, &$changed) {
                $previousScore = (int) $profile->score;

                if ($profileScoreService->recalculate($profile) !== $previousScore) {
                    $changed++;
                }

                $total++;
            });

            $rows[] = [$type, $total, $changed];
        }

        $this->table(['Perfil', 'Recalculados', 'Alterados'], $rows);

        return self::SUCCESS;
    }
}
