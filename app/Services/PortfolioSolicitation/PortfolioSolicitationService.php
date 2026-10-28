<?php

namespace App\Services\PortfolioSolicitation;

use App\Enums\PortfolioSolicitationStatusEnum;
use App\Enums\PortfolioSolicitationTypeEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\PortfolioSolicitation\PortfolioSolicitationResource;
use App\Models\PortfolioSolicitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PortfolioSolicitationService {

    /**
     * Hosts tratados como repositório de código
     */
    private const REPOSITORY_HOSTS = [
        'github.com'
    ];

    /**
     * Registra a url do portfólio na solicitação do desenvolvedor autenticado.
     * O tipo é identificado a partir da url e a solicitação passa a ser considerada enviada
     */
    public function update(array $data): PortfolioSolicitationResource {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't send a portfolio for this solicitation!");
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $solicitation = PortfolioSolicitation::query()->with(['devJobVacancy.jobVacancy', 'devProfile'])
            ->where('id', $data['id'])
            ->where('dev_profile_id', $devProfile->id)
            ->first();

        if(!$solicitation) {
            throw new ApiException("This portfolio solicitation does not belong to you!", 403);
        }

        return DB::transaction(function() use ($solicitation, $data) {

            $solicitation->update([
                'portfolio_url' => $data['portfolio_url'],
                'type' => $this->resolvePortfolioType($data['portfolio_url']),
                // Com a url preenchida a solicitação deixa de estar pendente
                'status' => PortfolioSolicitationStatusEnum::SENT
            ]);

            return new PortfolioSolicitationResource($solicitation);

        });

    }

    /**
     * Identifica se a url enviada é de um repositório de código ou de um projeto
     * já hospedado
     */
    private function resolvePortfolioType(string $portfolioUrl): PortfolioSolicitationTypeEnum {

        $host = strtolower(parse_url($portfolioUrl, PHP_URL_HOST) ?? '');

        // Ignora o www. para comparar apenas o domínio
        $host = preg_replace('/^www\./', '', $host);

        return in_array($host, self::REPOSITORY_HOSTS, true)
            ? PortfolioSolicitationTypeEnum::REPOSITORY
            : PortfolioSolicitationTypeEnum::PRODUCTION;

    }

}
