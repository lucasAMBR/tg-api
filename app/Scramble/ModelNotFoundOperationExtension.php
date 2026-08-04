<?php

namespace App\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Anexa a ModelNotFoundException a todo endpoint cuja rota tem parâmetro de
 * identificador ({id}, {user_id}, {instanceId} etc.).
 *
 * O padrão do projeto usa Request + findOrFail() no Service (sem route model
 * binding), então o Scramble não consegue inferir o 404 sozinho — esta
 * extensão replica o comportamento de ErrorResponsesExtension sem exigir
 * que o parâmetro de path seja um model ID de binding.
 */
class ModelNotFoundOperationExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if (! $methodType = $routeInfo->getActionType()) {
            return;
        }

        $hasIdParameter = collect($routeInfo->route->parameterNames())
            ->contains(fn (string $name) => str_ends_with(strtolower($name), 'id'));

        if (! $hasIdParameter) {
            return;
        }

        $alreadyAttached = collect($methodType->exceptions)
            ->contains(fn (Type $e) => $e->isInstanceOf(ModelNotFoundException::class));

        if ($alreadyAttached) {
            return;
        }

        $methodType->exceptions = [
            ...$methodType->exceptions,
            new ObjectType(ModelNotFoundException::class),
        ];
    }
}