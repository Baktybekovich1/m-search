<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ExampleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,
        
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,
    ) {
    }
}
