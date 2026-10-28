<?php

namespace App\Scramble;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Documenta a ValidationException com o envelope padrão da API (ReturnApi),
 * conforme o handler registrado em bootstrap/app.php.
 */
class ValidationExceptionToResponseExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(ValidationException::class);
    }

    public function toResponse(Type $type): Response
    {
        $validationResponseBodyType = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'error',
                (new OpenApiTypes\BooleanType)
                    ->example(true)
            )
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)
                    ->setDescription('Mensagem do primeiro erro de validação.')
            )
            ->addProperty(
                'data',
                (new OpenApiTypes\ObjectType)
                    ->setDescription('Erros detalhados por campo que falhou na validação.')
                    ->additionalProperties((new OpenApiTypes\ArrayType)->setItems(new OpenApiTypes\StringType))
            )
            ->setRequired(['error', 'message', 'data']);

        return Response::make(422)
            ->setDescription('Erro de validação')
            ->setContent(
                'application/json',
                Schema::fromType($validationResponseBodyType),
            );
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('responses', Str::start($type->name, '\\'), $this->components);
    }
}