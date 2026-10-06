<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\IrreversibleMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006110755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les tables MyISAM en InnoDB puis active la clé étrangère Figurine-User.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE figurine ENGINE = InnoDB');
        $this->addSql('ALTER TABLE messenger_messages ENGINE = InnoDB');
        $this->addSql('ALTER TABLE figurine ADD CONSTRAINT FK_9DD6478F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('La migration préserve les données et ne supprime pas de clé ou de table au retour arrière.');
    }
}
