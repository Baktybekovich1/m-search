<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateRequest
{
    public function __construct(
        #[Assert\Length(max: 20)]
        public readonly ?string $phoneNumber = null,
    ) {
    }
}
