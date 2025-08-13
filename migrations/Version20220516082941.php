<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220516082941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_time_slot (workshop_id INT NOT NULL, time_slot_id INT NOT NULL, nb_guests INT DEFAULT NULL, INDEX IDX_F7899EDB1FDCE57C (workshop_id), INDEX IDX_F7899EDBD62B0FA (time_slot_id), PRIMARY KEY(workshop_id, time_slot_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDB1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDBD62B0FA FOREIGN KEY (time_slot_id) REFERENCES time_slot (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE workshop_time_slot');
    }
}
