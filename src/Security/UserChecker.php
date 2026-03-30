<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Ваш email не подтвержден. Пожалуйста, введите код подтверждения.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token = null): void
    {
        // No post-auth checks needed mapped to verified status
    }
}
