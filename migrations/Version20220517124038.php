<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220517124038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE guest_workshop');
        $this->addSql('ALTER TABLE guest ADD workshop_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workshop_time_slot DROP FOREIGN KEY FK_F7899EDB1FDCE57C');
        $this->addSql('ALTER TABLE workshop_time_slot DROP FOREIGN KEY FK_F7899EDBD62B0FA');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDB1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDBD62B0FA FOREIGN KEY (time_slot_id) REFERENCES time_slot (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE guest_workshop (guest_id INT NOT NULL, workshop_id INT NOT NULL, INDEX IDX_E445CABB9A4AA658 (guest_id), INDEX IDX_E445CABB1FDCE57C (workshop_id), PRIMARY KEY(guest_id, workshop_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE guest_workshop ADD CONSTRAINT FK_E445CABB1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest_workshop ADD CONSTRAINT FK_E445CABB9A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest DROP workshop_id');
        $this->addSql('ALTER TABLE workshop_time_slot DROP FOREIGN KEY FK_F7899EDB1FDCE57C');
        $this->addSql('ALTER TABLE workshop_time_slot DROP FOREIGN KEY FK_F7899EDBD62B0FA');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDB1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE workshop_time_slot ADD CONSTRAINT FK_F7899EDBD62B0FA FOREIGN KEY (time_slot_id) REFERENCES time_slot (id)');
    }
}
