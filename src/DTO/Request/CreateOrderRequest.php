<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public readonly string $address,

        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\GreaterThan(0)
        ])]
        public readonly array $productIds,
    ) {
    }
}
