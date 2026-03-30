<?php
namespace App\Service;

use App\DTO\Request\ForgotPasswordRequest;
use App\DTO\Request\RegisterRequest;
use App\DTO\Request\ResetPasswordRequest;
use App\DTO\Request\VerifyRequest;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService {
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerService $mailerService
    ) {}

    public function register(RegisterRequest $request): void {
        $userRepo = $this->entityManager->getRepository(User::class);
        if ($userRepo->findOneBy(['email' => $request->email])) {
            throw new ApiException('Пользователь с таким email уже существует', 409);
        }

        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setVerificationCode($code);
        $user->setVerified(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->mailerService->sendVerificationCode($user->getEmail(), $code);
    }

    public function verify(VerifyRequest $request): void {
        $userRepo = $this->entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $request->email]);

        if (!$user) {
            throw new ApiException('Пользователь не найден', 404);
        }

        if ($user->isVerified()) {
            throw new ApiException('Email уже подтвержден', 400);
        }

        if ($user->getVerificationCode() !== $request->code) {
            throw new ApiException('Неверный код подтверждения', 400);
        }

        $user->setVerified(true);
        $user->setVerificationCode(null);
        $this->entityManager->flush();
    }

    public function forgotPassword(ForgotPasswordRequest $request): void {
        $userRepo = $this->entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $request->email]);

        if (!$user) {
            // Silently return to avoid email enumeration
            return;
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setResetToken($code);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->flush();

        $this->mailerService->sendResetCode($user->getEmail(), $code);
    }

    public function resetPassword(ResetPasswordRequest $request): void {
        $userRepo = $this->entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $request->email]);

        if (!$user) {
            throw new ApiException('Пользователь не найден', 404);
        }

        if ($user->getResetToken() === null || $user->getResetToken() !== $request->token) {
            throw new ApiException('Неверный код сброса', 400);
        }

        if ($user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new ApiException('Срок действия кода истек', 400);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->flush();
    }
}
