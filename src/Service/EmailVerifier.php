<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailVerifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderAddress,
    ) {
    }

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new Email())
            ->from($this->senderAddress)
            ->to((string) $user->getEmail())
            ->subject('Vérification de votre compte FigurineVite')
            ->text(sprintf(
                "Bonjour %s,\n\nMerci pour votre inscription sur FigurineVite.\n\nCliquez sur le lien ci-dessous pour vérifier votre adresse email :\n\n%s\n\nÀ bientôt,\nFigurineVite",
                $user->getFirstname(),
                $verificationUrl,
            ));

        $this->mailer->send($email);
    }
}
