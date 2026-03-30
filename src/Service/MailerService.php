<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService {
    public function __construct(private readonly MailerInterface $mailer) {}

    public function sendVerificationCode(string $toEmail, string $code): void {
        $email = (new Email())
            ->from('noreply@msearch.local')
            ->to($toEmail)
            ->subject('Подтверждение Email')
            ->text(sprintf('Ваш код подтверждения: %s', $code));

        $this->mailer->send($email);
    }

    public function sendResetCode(string $toEmail, string $code): void {
        $email = (new Email())
            ->from('noreply@msearch.local')
            ->to($toEmail)
            ->subject('Восстановление пароля')
            ->text(sprintf('Ваш код для сброса пароля: %s', $code));

        $this->mailer->send($email);
    }
}
