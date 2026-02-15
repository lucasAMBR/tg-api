<?php

namespace App\Exceptions;

use App\Builder\ApiResponse;
use Exception;
use Throwable;

class ApiException extends Exception
{

    public function __construct(
        string $message = "Erro inesperado",
        int $code = 500,
        public array $data = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            data: $this->data,
            status: $this->getCode()
        );
    }
}
