<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ProductRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $title,
        
        #[Assert\NotBlank]
        public readonly string $description,
        
        #[Assert\NotBlank]
        public readonly string $mainPhoto,
        
        #[Assert\Type('array')]
        public readonly ?array $auxiliaryPhotos = null,
        
        #[Assert\All([
            new Assert\Type('integer')
        ])]
        public readonly array $categoryIds = [],

        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(0)]
        public readonly ?int $price = null,
    ) {
    }
}
