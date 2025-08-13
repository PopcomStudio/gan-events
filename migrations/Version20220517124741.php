<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220517124741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guest_workshop_time_slot ADD CONSTRAINT FK_81423FC29A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest_workshop_time_slot ADD CONSTRAINT FK_81423FC29C3B8306 FOREIGN KEY (workshop_time_slot_id) REFERENCES workshop_time_slot (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guest_workshop_time_slot DROP FOREIGN KEY FK_81423FC29A4AA658');
        $this->addSql('ALTER TABLE guest_workshop_time_slot DROP FOREIGN KEY FK_81423FC29C3B8306');
    }
}
