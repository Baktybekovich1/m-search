<?php
namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyRequest {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(exactly: 6)]
        public readonly string $code,
    ) {}
}
