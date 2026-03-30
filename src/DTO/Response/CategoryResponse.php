<?php

namespace App\DTO\Response;

class CategoryResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
