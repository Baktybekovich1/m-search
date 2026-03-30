<?php

namespace App\DTO\Response;

class ProductResponse
{
    /**
     * @param array<CategoryResponse> $categories
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $mainPhoto,
        public readonly ?array $auxiliaryPhotos,
        public readonly array $categories,
        public readonly ?int $ownerId,
        public readonly ?int $price,
    ) {
    }
}
