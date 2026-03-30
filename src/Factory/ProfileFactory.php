<?php

namespace App\Factory;

use App\DTO\Request\ProfileUpdateRequest;
use App\DTO\Response\ProfileResponse;
use App\Entity\User;

class ProfileFactory
{
    public function updateEntityFromRequest(User $user, ProfileUpdateRequest $request): void
    {
        if ($request->phoneNumber !== null) {
            $user->setPhoneNumber($request->phoneNumber);
        }
    }

    public function createResponse(User $user): ProfileResponse
    {
        return new ProfileResponse(
            id: $user->getId(),
            email: $user->getEmail(),
            phoneNumber: $user->getPhoneNumber(),
            isVerified: $user->isVerified(),
            roles: $user->getRoles()
        );
    }
}
