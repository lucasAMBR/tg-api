<?php

namespace App\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\RouteInfo;

/**
 * Remove da requisição os parâmetros que pertencem ao path da rota.
 *
 * O projeto valida o parâmetro de rota dentro do próprio FormRequest (via
 * `prepareForValidation()` + regra `exists`), então a chave aparece em `rules()`.
 * O Scramble então a duplicaria no corpo (POST/PUT) ou na query string (GET).
 * Esta extensão garante que parâmetros de path sejam documentados apenas como
 * path parameters.
 */
class RemovePathParametersFromRequestOperationExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $pathParameters = $routeInfo->route->parameterNames();

        if ($pathParameters === []) {
            return;
        }

        $this->removeFromQuery($operation, $pathParameters);
        $this->removeFromBody($operation, $pathParameters);
    }

    /**
     * @param  list<string>  $pathParameters
     */
    private function removeFromQuery(Operation $operation, array $pathParameters): void
    {
        $operation->parameters = array_values(array_filter(
            $operation->parameters,
            fn ($parameter): bool => ! (
                $parameter instanceof Parameter
                && $parameter->in === 'query'
                && in_array($parameter->name, $pathParameters, true)
            ),
        ));
    }

    /**
     * @param  list<string>  $pathParameters
     */
    private function removeFromBody(Operation $operation, array $pathParameters): void
    {
        if ($operation->requestBodyObject === null) {
            return;
        }

        foreach ($operation->requestBodyObject->content as $schemaOrReference) {
            // O corpo costuma ser um $ref para um componente (ex.: UpdateStudyBaseRequest);
            // resolvemos a referência para mutar o schema real do componente.
            $schema = $schemaOrReference instanceof Reference
                ? $schemaOrReference->resolve()
                : $schemaOrReference;

            if (! $schema instanceof Schema || ! $schema->type instanceof ObjectType) {
                continue;
            }

            $type = $schema->type;

            foreach ($pathParameters as $name) {
                unset($type->properties[$name]);
            }

            $type->required = array_values(array_diff($type->required, $pathParameters));
        }
    }
}