<?php

namespace App\Scramble;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Documenta respostas 404 com o envelope padrão da API (ReturnApi),
 * conforme o handler registrado em bootstrap/app.php.
 */
class NotFoundExceptionToResponseExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && (
                $type->isInstanceOf(RecordsNotFoundException::class)
                || $type->isInstanceOf(NotFoundHttpException::class)
            );
    }

    public function toResponse(Type $type): Response
    {
        $responseBodyType = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'error',
                (new OpenApiTypes\BooleanType)
                    ->example(true)
            )
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)
                    ->example('Recurso não encontrado.')
            )
            ->addProperty('data', new OpenApiTypes\NullType)
            ->setRequired(['error', 'message', 'data']);

        return Response::make(404)
            ->setDescription('Recurso não encontrado')
            ->setContent(
                'application/json',
                Schema::fromType($responseBodyType),
            );
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('responses', Str::start($type->name, '\\'), $this->components);
    }
}