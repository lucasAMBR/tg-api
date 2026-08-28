<?php

namespace App\Http\Controllers\PortfolioSolicitation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioSolicitation\UpdatePortfolioSolicitationRequest;
use App\Services\PortfolioSolicitation\PortfolioSolicitationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class PortfolioSolicitationController extends Controller
{
    public function __construct(protected PortfolioSolicitationService $portfolioSolicitationService) {}

    #[Endpoint(operationId: 'updatePortfolioSolicitation', title: 'Enviar portfólio solicitado', description: '**operationId:** `updatePortfolioSolicitation` — Registra a `portfolio_url` da solicitação de portfólio do desenvolvedor autenticado (exige role `dev`; solicitações de outro desenvolvedor respondem **403**). O `type` é identificado automaticamente pela url — `repository` para endereços do GitHub e `production` para os demais — e o `status` passa a ser `sent`. Em **200**, `data` segue o schema **Portfolio Solicitation Resource** (`App\\Http\\Resources\\PortfolioSolicitation\\PortfolioSolicitationResource`).')]
    public function update(UpdatePortfolioSolicitationRequest $request): JsonResponse {

        try {
            $data = $this->portfolioSolicitationService->update($request->validated());

            return ApiResponse::success(
                $data,
                'Portfolio sent with success!',
                200
            );
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

}
