<?php

namespace App\Http\Controllers\FreelanceFeed;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FreelanceFeed\DestroyFreelanceFeedPostRequest;
use App\Http\Requests\FreelanceFeed\IndexFreelanceFeedRequest;
use App\Http\Requests\FreelanceFeed\StoreFreelanceFeedPostRequest;
use App\Http\Resources\FreelanceFeed\FreelanceFeedPostResource;
use App\Services\FreelanceFeed\FreelanceFeedService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class FreelanceFeedController extends Controller
{
    public function __construct(protected FreelanceFeedService $freelanceFeedService) {}

    #[Endpoint(operationId: 'indexFreelanceFeed', title: 'Listar o feed do freela', description: '**operationId:** `indexFreelanceFeed` — Lista paginada das postagens do feed do freela, da mais recente para a mais antiga. Só o cliente dono e o dev contratado, e só depois do contrato fechado (`in_progress` ou `completed`). Aceita o filtro opcional `type` (`update`, `deliverable`, `review`). Os anexos vêm com `url` assinada que expira em 30 minutos. Em **200**, `data.data[]` segue o schema **Freelance Feed Post Resource** (`App\\Http\\Resources\\FreelanceFeed\\FreelanceFeedPostResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexFreelanceFeedRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceFeedService->index($request->validated());

            return ApiResponse::success(
                $data,
                'Freelance feed indexed with success!',
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

    #[Endpoint(operationId: 'storeFreelanceFeedPost', title: 'Publicar no feed do freela', description: '**operationId:** `storeFreelanceFeedPost` — Publica no feed do freela em andamento (`multipart/form-data`). `type`: `update` (qualquer uma das pontas), `deliverable` (só o dev contratado) ou `review` (só o cliente, com `parent_id` apontando uma entrega do feed e `review_status`: `approved` ou `changes_requested`). Exige `content` ou `attachments` (até 10 arquivos de até 10 MB: pdf, doc, docx, xls, xlsx, csv, ppt, pptx, txt, md, zip, png, jpg, jpeg, webp). A outra ponta é notificada. Em **201**, `data` segue o schema **Freelance Feed Post Resource** (`App\\Http\\Resources\\FreelanceFeed\\FreelanceFeedPostResource`).')]
    public function store(StoreFreelanceFeedPostRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceFeedService->store($request->validated());

            return ApiResponse::success(
                new FreelanceFeedPostResource($data),
                'Freelance feed post created with success!',
                201
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

    #[Endpoint(operationId: 'deleteFreelanceFeedPost', title: 'Remover postagem do feed do freela', description: '**operationId:** `deleteFreelanceFeedPost` — O autor remove a própria postagem do feed, enquanto o freela está em andamento. Em **200**, `data` é `null`.')]
    public function destroy(DestroyFreelanceFeedPostRequest $request): JsonResponse
    {
        try {
            $this->freelanceFeedService->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Freelance feed post deleted with success!',
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
