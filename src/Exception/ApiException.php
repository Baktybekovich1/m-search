<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(string $message, int $statusCode = 400, \Throwable $previous = null)
    {
        parent::__construct($statusCode, $message, $previous);
    }
}
