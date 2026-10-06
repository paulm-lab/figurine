<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private \Doctrine\DBAL\Connection $connection;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        parent::tearDown();
    }

    public function testPasswordResetRequestValidationAndLogin(): void
    {
        $client = $this->client;

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $email = sprintf('password-reset-%s@figurine.test', bin2hex(random_bytes(6)));
        $user = (new User())
            ->setFirstname('Test')
            ->setLastname('Password')
            ->setEmail($email)
            ->setImageName('user-placeholder.svg')
            ->setIsVerified(true);
        $user->setPassword($hasher->hashPassword($user, 'AncienMotDePasse123!'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $forgotPage = $client->request('GET', '/mot-de-passe-oublie');
        self::assertResponseIsSuccessful();
        $forgotForm = $forgotPage->selectButton('Envoyer le lien')->form();
        $forgotForm['form[email]'] = $email;
        $client->submit($forgotForm);
        self::assertResponseRedirects('/mot-de-passe-oublie');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-info', 'Si un compte correspond');

        $user = $this->reloadUser($email);
        self::assertNotNull($user->getPasswordResetToken());
        self::assertNotNull($user->getPasswordResetExpiresAt());

        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken(hash('sha256', $token));
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        $resetPage = $client->request('GET', '/mot-de-passe-oublie/reinitialiser/'.$token);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Nouveau mot de passe');

        $invalidForm = $resetPage->selectButton('Enregistrer le nouveau mot de passe')->form();
        $invalidForm['form[plainPassword][first]'] = 'court';
        $invalidForm['form[plainPassword][second]'] = 'different';
        $client->submit($invalidForm);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSelectorTextContains('body', 'Les deux mots de passe doivent être identiques.');

        $validForm = $client->getCrawler()->selectButton('Enregistrer le nouveau mot de passe')->form();
        $validForm['form[plainPassword][first]'] = 'NouveauMotDePasse123!';
        $validForm['form[plainPassword][second]'] = 'NouveauMotDePasse123!';
        $client->submit($validForm);
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $user = $this->reloadUser($email);
        self::assertNull($user->getPasswordResetToken());
        self::assertNull($user->getPasswordResetExpiresAt());
        self::assertTrue($hasher->isPasswordValid($user, 'NouveauMotDePasse123!'));

        $loginForm = $client->getCrawler()->selectButton('Se connecter')->form();
        $loginForm['email'] = $email;
        $loginForm['password'] = 'NouveauMotDePasse123!';
        $client->submit($loginForm);
        self::assertResponseRedirects('/');
    }

    public function testInvalidAndExpiredTokensRenderRecoveryMessageInsteadOf404(): void
    {
        $client = $this->client;

        $client->request('GET', '/mot-de-passe-oublie/reinitialiser/token-invalide');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Ce lien de réinitialisation est invalide ou expiré.');

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $email = sprintf('password-expired-%s@figurine.test', bin2hex(random_bytes(6)));
        $user = (new User())
            ->setFirstname('Test')
            ->setLastname('Expired')
            ->setEmail($email)
            ->setImageName('user-placeholder.svg')
            ->setIsVerified(true)
            ->setPasswordResetToken(hash('sha256', 'expired-test-token'))
            ->setPasswordResetExpiresAt(new \DateTimeImmutable('-1 minute'));
        $user->setPassword($hasher->hashPassword($user, 'AncienMotDePasse123!'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $client->request('GET', '/mot-de-passe-oublie/reinitialiser/expired-test-token');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Ce lien de réinitialisation est invalide ou expiré.');

        $user = $this->reloadUser($email);
        self::assertNull($user->getPasswordResetToken());
        self::assertNull($user->getPasswordResetExpiresAt());
    }

    private function reloadUser(string $email): User
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
