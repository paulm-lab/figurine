<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier,
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $entityManager->persist($user);
            $entityManager->flush();
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );

            try {
                $emailVerifier->sendVerificationEmail($user, $verificationUrl);
                $this->addFlash('info', sprintf(
                    'Bienvenue %s ! Votre compte a été créé. Consultez votre boîte Mailtrap pour vérifier votre adresse.',
                    $user->getFirstname(),
                ));

                return $this->render('registration/verify.html.twig', ['emailSent' => true]);
            } catch (TransportExceptionInterface) {
                $this->addFlash('danger', 'Le compte a été créé, mais l’envoi de l’e-mail a échoué. Vérifiez les paramètres SMTP Mailtrap dans .env.local.');

                return $this->render('registration/verify.html.twig', [
                    'emailSent' => false,
                    'verificationUrl' => $verificationUrl,
                ]);
            }
        }
        return $this->render('registration/register.html.twig', ['form' => $form]);
    }

    #[Route('/verify/email', name: 'app_verify_email', methods: ['GET'])]
    public function verify(Request $request, UserRepository $users, EntityManagerInterface $entityManager): Response
    {
        $token = $request->query->getString('token');
        if ($token !== '') {
            $user = $users->findOneBy(['verificationToken' => $token]);
            if ($user instanceof User) {
                $user->setIsVerified(true)->setVerificationToken(null);
                $entityManager->flush();
                $this->addFlash('success', 'Votre adresse e-mail est vérifiée. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', 'Ce lien de vérification est invalide ou a déjà été utilisé.');
        }
        return $this->render('registration/verify.html.twig');
    }
}
