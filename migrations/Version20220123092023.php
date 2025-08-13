<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220123092023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE guest_workshop (guest_id INT NOT NULL, workshop_id INT NOT NULL, INDEX IDX_E445CABB9A4AA658 (guest_id), INDEX IDX_E445CABB1FDCE57C (workshop_id), PRIMARY KEY(guest_id, workshop_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, name VARCHAR(255) NOT NULL, time_slot VARCHAR(255) DEFAULT NULL, INDEX IDX_9B6F02C471F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE guest_workshop ADD CONSTRAINT FK_E445CABB9A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest_workshop ADD CONSTRAINT FK_E445CABB1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C471F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event ADD min_workshop INT NOT NULL, ADD max_workshop INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guest_workshop DROP FOREIGN KEY FK_E445CABB1FDCE57C');
        $this->addSql('DROP TABLE guest_workshop');
        $this->addSql('DROP TABLE workshop');
        $this->addSql('ALTER TABLE event DROP min_workshop, DROP max_workshop');
    }
}
