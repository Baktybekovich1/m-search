<?php

namespace App\DTO\Response;

class ExampleResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $message,
    ) {
    }
}
