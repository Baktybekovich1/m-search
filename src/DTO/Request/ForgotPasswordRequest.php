<?php
namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordRequest {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,
    ) {}
}
