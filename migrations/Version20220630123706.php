<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630123706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sender ADD guest_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('UPDATE sender s SET s.guest_type = (SELECT ev.guest_type FROM event ev WHERE ev.id = s.event_id)');
        $this->addSql('ALTER TABLE event DROP guest_type');
        $this->addSql('ALTER TABLE sender CHANGE guest_type guest_type VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD guest_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('UPDATE `event` ev SET ev.guest_type = (SELECT s.guest_type FROM sender s WHERE s.event_id = ev.id LIMIT 1)');
        $this->addSql('UPDATE `event` SET guest_type = "all" WHERE guest_type IS NULL');
        $this->addSql('ALTER TABLE sender DROP guest_type');
        $this->addSql('ALTER TABLE event CHANGE guest_type guest_type VARCHAR(100) NOT NULL');
    }
}
