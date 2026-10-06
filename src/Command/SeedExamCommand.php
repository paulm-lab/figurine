<?php

namespace App\Command;

use App\Entity\Figurine;
use App\Entity\User;
use App\Repository\FigurineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-exam', description: 'Crée ou complète les utilisateurs et figurines de démonstration.')]
class SeedExamCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly FigurineRepository $figurines,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $accounts = [
            ['Paul', 'Collectionneur', 'user1@figurine.test'],
            ['Camille', 'Passionnée', 'user2@figurine.test'],
            ['Alex', 'Figurines', 'user3@figurine.test'],
        ];
        $owners = [];
        foreach ($accounts as [$firstname, $lastname, $email]) {
            $user = $this->users->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                $user = (new User())->setFirstname($firstname)->setLastname($lastname)->setEmail($email)->setRoles(['ROLE_USER'])->setImageName('user-placeholder.svg')->setIsVerified(true);
                $user->setPassword($this->passwordHasher->hashPassword($user, '123'));
                $this->entityManager->persist($user);
            }
            $owners[] = $user;
        }

        $catalogue = [
            ['Chevalier du royaume', 'Armure argentée et bouclier gravé, pièce idéale pour une collection médiévale.', 24.90],
            ['Dragon des montagnes', 'Dragon aux écailles rouges posé sur un socle rocheux.', 39.00],
            ['Robot explorateur', 'Petit robot articulé équipé de son sac de voyage.', 18.50],
            ['Gardienne de la forêt', 'Figurine peinte à la main avec un arc et un manteau vert.', 31.75],
            ['Pilote intergalactique', 'Combinaison de vol détaillée et casque amovible.', 27.00],
            ['Sorcier des glaces', 'Cape bleue translucide et bâton orné de cristaux.', 34.90],
            ['Chat samouraï', 'Armure miniature inspirée des guerriers japonais.', 22.00],
            ['Capitaine des mers', 'Capitaine au long manteau, accompagné de son sabre.', 29.50],
            ['Golem de pierre', 'Créature massive composée de roches sculptées.', 42.00],
            ['Inventrice mécanique', 'Inventrice avec lunettes, outils et sacoche.', 26.40],
            ['Phénix solaire', 'Oiseau mythique aux ailes déployées et aux couleurs chaudes.', 37.90],
            ['Ranger de la vallée', 'Éclaireur avec cape, carquois et compagnon renard.', 25.00],
        ];

        $created = 0;
        foreach ($catalogue as $index => [$title, $description, $price]) {
            $owner = $owners[$index % count($owners)];
            if ($this->figurines->findOneBy(['title' => $title, 'author' => $owner])) { continue; }
            $figurine = (new Figurine())
                ->setTitle($title)
                ->setDescription($description)
                ->setImageName('figurine-placeholder.svg')
                ->setPrix($price)
                ->setAuthor($owner);
            $this->entityManager->persist($figurine);
            ++$created;
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('<info>Données prêtes : 3 comptes de démonstration et %d nouvelle(s) figurine(s) (12 figurines attendues).</info>', $created));
        $output->writeln('Mot de passe des nouveaux comptes : 123');
        return Command::SUCCESS;
    }
}
