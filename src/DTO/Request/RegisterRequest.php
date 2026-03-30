<?php
namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $password,
    ) {}
}
