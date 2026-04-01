<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class CreateOrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        #[OA\Property(description: 'Адрес доставки', example: 'г. Бишкек, ул. Абдрахманова 123')]
        public readonly string $address,

        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\GreaterThan(0)
        ])]
        #[OA\Property(
            description: 'Список ID товаров для покупки',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1)
        )]
        public readonly array $productIds,
    ) {
    }
}
