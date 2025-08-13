<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220517124548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE guest_workshop_time_slot (guest_id INT NOT NULL, workshop_time_slot_id INT NOT NULL, INDEX IDX_81423FC29A4AA658 (guest_id), INDEX IDX_81423FC29C3B8306 (workshop_time_slot_id), PRIMARY KEY(guest_id, workshop_time_slot_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE guest DROP workshop_id');
        $this->addSql('ALTER TABLE workshop_time_slot DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE workshop_time_slot ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE guest_workshop_time_slot');
        $this->addSql('ALTER TABLE guest ADD workshop_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workshop_time_slot MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_time_slot DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE workshop_time_slot DROP id');
        $this->addSql('ALTER TABLE workshop_time_slot ADD PRIMARY KEY (workshop_id, time_slot_id)');
    }
}
