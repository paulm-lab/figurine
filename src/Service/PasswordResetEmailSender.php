<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class PasswordResetEmailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderAddress,
    ) {
    }

    public function send(User $user, string $resetUrl): void
    {
        $firstName = htmlspecialchars((string) $user->getFirstname(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $email = (new Email())
            ->from($this->senderAddress)
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe FigurineVite')
            ->text(sprintf(
                "Bonjour %s,\n\nPour choisir un nouveau mot de passe, ouvrez ce lien (valable une heure) :\n%s\n\nSi vous n’avez pas demandé cette réinitialisation, ignorez ce message.\n\nFigurineVite",
                $user->getFirstname(),
                $resetUrl,
            ))
            ->html(sprintf(
                '<p>Bonjour %s,</p><p>Pour choisir un nouveau mot de passe, cliquez sur le lien ci-dessous. Il est valable une heure.</p><p><a href="%s">Réinitialiser mon mot de passe</a></p><p>Si vous n’avez pas demandé cette réinitialisation, ignorez ce message.</p><p>FigurineVite</p>',
                $firstName,
                $safeUrl,
            ));

        $this->mailer->send($email);
    }
}
