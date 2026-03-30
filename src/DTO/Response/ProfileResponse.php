<?php

namespace App\DTO\Response;

class ProfileResponse
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $phoneNumber,
        public readonly bool $isVerified,
        public readonly array $roles,
    ) {
    }
}
