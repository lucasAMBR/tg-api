<?php

namespace App\Http\Controllers\Post;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\DeletePostRequest;
use App\Http\Requests\Post\IndexFavoritePostRequest;
use App\Http\Requests\Post\IndexPostRequest;
use App\Http\Requests\Post\RemovePostImageRequest;
use App\Http\Requests\Post\SavePostImagesRequest;
use App\Http\Requests\Post\ShowPostRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\ToggleFavoritePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Services\Post\PostService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct(protected PostService $postService){}

    #[Endpoint(operationId: 'indexPost', title: 'Listar feed de postagens', description: '**operationId:** `indexPost` — Feed paginado de postagens, das mais recentes para as mais antigas, com autor, imagens, contadores de curtidas/comentários/favoritos e se o usuário autenticado curtiu/favoritou. Filtros opcionais por `user_id` e `search` (busca no conteúdo). Em **200**, `data.data[]` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexPostRequest $request): JsonResponse
    {
        try {
            $posts = $this->postService->index($request->validated());

            return ApiResponse::success($posts, "Posts indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexFavoritePosts', title: 'Listar postagens favoritas', description: '**operationId:** `indexFavoritePosts` — Lista paginada das postagens favoritadas pelo usuário autenticado, com filtro opcional por `search`. Em **200**, `data.data[]` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexFavorites(IndexFavoritePostRequest $request): JsonResponse
    {
        try {
            $posts = $this->postService->indexFavorites($request->validated());

            return ApiResponse::success($posts, "Favorite posts indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showPost', title: 'Consultar postagem', description: '**operationId:** `showPost` — Retorna uma postagem pelo identificador na rota. Em **200**, `data` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`).')]
    public function show(ShowPostRequest $request): JsonResponse
    {
        try {
            $post = $this->postService->show($request->validated());

            return ApiResponse::success($post, "Post found!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storePost', title: 'Publicar postagem', description: '**operationId:** `storePost` — Publica uma postagem do usuário autenticado (qualquer perfil). Aceita até 10 imagens em `images[]` (multipart). Em **201**, `data` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`).')]
    public function store(StorePostRequest $request): JsonResponse
    {
        try {
            $post = $this->postService->store($request->validated());

            return ApiResponse::success($post, "Post created with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updatePost', title: 'Atualizar postagem', description: '**operationId:** `updatePost` — Atualiza o texto de uma postagem. Apenas o autor pode editar (`PostPolicy::update`). Em **200**, `data` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`).')]
    public function update(UpdatePostRequest $request): JsonResponse
    {
        try {
            $post = $this->postService->update($request->validated());

            return ApiResponse::success($post, "Post updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deletePost', title: 'Remover postagem', description: '**operationId:** `deletePost` — Remove (soft delete) uma postagem. Permitido ao autor ou a um admin (`PostPolicy::delete`). Em **200**, `data` é `null`.')]
    public function delete(DeletePostRequest $request): JsonResponse
    {
        try {
            $this->postService->delete($request->validated());

            return ApiResponse::success(message: "Post removed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'toggleFavoritePost', title: 'Favoritar/desfavoritar postagem', description: '**operationId:** `toggleFavoritePost` — Alterna a postagem na lista de favoritos do usuário autenticado. Em **200**, `data` traz `favorited` (estado final) e `favorites_count`.')]
    public function toggleFavorite(ToggleFavoritePostRequest $request): JsonResponse
    {
        try {
            $favorite = $this->postService->toggleFavorite($request->validated());

            return ApiResponse::success($favorite, "Post favorite updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storePostImages', title: 'Adicionar imagens à postagem', description: '**operationId:** `storePostImages` — Anexa as imagens enviadas em `images[]` à postagem, respeitando o limite de 10 por postagem. Apenas o autor (`PostPolicy::update`). Em **200**, `data` segue o schema **Post Resource** (`App\\Http\\Resources\\Post\\PostResource`).')]
    public function saveImages(SavePostImagesRequest $request): JsonResponse
    {
        try {
            $post = $this->postService->saveImages($request->validated());

            return ApiResponse::success($post, "Images saved with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deletePostImage', title: 'Remover imagem da postagem', description: '**operationId:** `deletePostImage` — Remove a imagem informada da postagem. Apenas o autor (`PostPolicy::update`) e a imagem precisa pertencer à postagem. Em **200**, `data` é `null`.')]
    public function removeImage(RemovePostImageRequest $request): JsonResponse
    {
        try {
            $this->postService->removeImage($request->validated());

            return ApiResponse::success(message: "Image removed with success!");
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
