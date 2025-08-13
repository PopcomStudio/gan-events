<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250522142157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mise à jour des relations entre Event et EventMoment';
    }

    public function up(Schema $schema): void
    {
        // Suppression des contraintes existantes
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE event_moment DROP FOREIGN KEY IF EXISTS FK_EVENT_MOMENT_EVENT');
        $this->addSql('DROP INDEX IF EXISTS IDX_EVENT_MOMENT_EVENT ON event_moment');
        
        // Recréation des contraintes
        $this->addSql('ALTER TABLE event_moment ADD CONSTRAINT FK_EVENT_MOMENT_EVENT FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_EVENT_MOMENT_EVENT ON event_moment (event_id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE event_moment DROP FOREIGN KEY FK_EVENT_MOMENT_EVENT');
        $this->addSql('DROP INDEX IDX_EVENT_MOMENT_EVENT ON event_moment');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
} 