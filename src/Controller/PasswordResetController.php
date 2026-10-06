<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordResetEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;

final class PasswordResetController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgot(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        PasswordResetEmailSender $emailSender,
        LoggerInterface $logger,
    ): Response {
        $form = $this->createFormBuilder([], ['csrf_token_id' => 'forgot_password'])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [new Assert\NotBlank(message: 'Saisissez votre adresse e-mail.'), new Assert\Email(message: 'Saisissez une adresse e-mail valide.')],
                'attr' => ['autocomplete' => 'email', 'class' => 'form-control'],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $user = $users->findOneBy(['email' => $email]);

            if ($user instanceof User) {
                $token = bin2hex(random_bytes(32));
                $user
                    ->setPasswordResetToken(hash('sha256', $token))
                    ->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );

                try {
                    $emailSender->send($user, $resetUrl);
                } catch (TransportExceptionInterface) {
                    $logger->warning('Password reset email could not be sent.');
                }
            }

            $this->addFlash('info', 'Si un compte correspond à cette adresse, un lien de réinitialisation lui a été envoyé.');

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', ['form' => $form]);
    }

    #[Route(
        '/mot-de-passe-oublie/reinitialiser/{token}',
        name: 'app_reset_password',
        methods: ['GET', 'POST'],
    )]
    public function reset(
        string $token,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $users->findOneBy(['passwordResetToken' => hash('sha256', $token)]);
        $now = new \DateTimeImmutable();

        if (!$user instanceof User || $user->getPasswordResetExpiresAt() === null || $user->getPasswordResetExpiresAt() <= $now) {
            if ($user instanceof User) {
                $user->setPasswordResetToken(null)->setPasswordResetExpiresAt(null);
                $entityManager->flush();
            }

            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou expiré. Demandez un nouveau lien.');

            return $this->render('security/reset_password.html.twig', ['form' => null]);
        }

        $form = $this->createFormBuilder([], ['csrf_token_id' => 'reset_password'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control'],
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Saisissez un nouveau mot de passe.'),
                    new Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setPasswordResetToken(null)->setPasswordResetExpiresAt(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', ['form' => $form]);
    }
}
