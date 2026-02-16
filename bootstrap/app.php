<?php

use App\Builder\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api/index.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (NotFoundHttpException $e) {
            return ApiResponse::error('Rota não encontrada.', $e->getMessage());
        });
        $exceptions->renderable(function (ValidationException $e, $request) {
            return ApiResponse::error($e->validator->errors()->first(), $e->validator->errors()->toArray(), 422);
        });
        $exceptions->renderable(function (MethodNotAllowedException $e) {
            return ApiResponse::error('Método não permitido.'.$e->getMessage());
        });
        $exceptions->renderable(function (BadMethodCallException $e) {
            return ApiResponse::error('Método não permitido.'.$e->getMessage());
        });
        $exceptions->renderable(function (AuthorizationException $e) {
            return ApiResponse::error('Método não permitido.'.$e->getMessage());
        });
        $exceptions->renderable(function (AccessDeniedHttpException $e) {
            return ApiResponse::error('Acesso negado.', $e->getMessage(), $e->getStatusCode());
        });
        $exceptions->render(function (Throwable $e) {
            return ApiResponse::error('Erro inesperado na API.', $e->getMessage());
        });
    })->create();
