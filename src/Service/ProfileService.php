<?php

namespace App\Service;

use App\DTO\Request\ProfileUpdateRequest;
use App\DTO\Response\ProfileResponse;
use App\Entity\User;
use App\Factory\ProfileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProfileService
{
    public function __construct(
        private readonly ProfileFactory $factory,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {}

    public function getProfile(): ProfileResponse
    {
        $user = $this->getUserOrThrow();
        return $this->factory->createResponse($user);
    }

    public function updateProfile(ProfileUpdateRequest $request): ProfileResponse
    {
        $user = $this->getUserOrThrow();
        $this->factory->updateEntityFromRequest($user, $request);
        $this->entityManager->flush();
        
        return $this->factory->createResponse($user);
    }

    private function getUserOrThrow(): User
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to access your profile.');
        }
        return $user;
    }
}
