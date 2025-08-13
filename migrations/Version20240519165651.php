<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240519165651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table event_moment et de la table de jointure guest_event_moment';
    }

    public function up(Schema $schema): void
    {
        // Création de la table event_moment si elle n'existe pas
        $this->addSql('CREATE TABLE IF NOT EXISTS event_moment (
            id INT AUTO_INCREMENT NOT NULL,
            event_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            begin_at DATETIME NOT NULL,
            finish_at DATETIME NOT NULL,
            description LONGTEXT DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_EVENT_MOMENT_EVENT (event_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE event_moment 
            ADD CONSTRAINT FK_EVENT_MOMENT_EVENT 
            FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');

        // On vérifie d'abord si la table existe déjà
        $this->addSql('DROP TABLE IF EXISTS guest_event_moment');
        
        $this->addSql('CREATE TABLE guest_event_moment (
            guest_id INT NOT NULL, 
            event_moment_id INT NOT NULL, 
            INDEX IDX_GUEST_EVENT_MOMENT_GUEST (guest_id), 
            INDEX IDX_GUEST_EVENT_MOMENT_MOMENT (event_moment_id), 
            PRIMARY KEY(guest_id, event_moment_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE guest_event_moment 
            ADD CONSTRAINT FK_GUEST_EVENT_MOMENT_GUEST 
            FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE');
            
        $this->addSql('ALTER TABLE guest_event_moment 
            ADD CONSTRAINT FK_GUEST_EVENT_MOMENT_MOMENT 
            FOREIGN KEY (event_moment_id) REFERENCES event_moment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS guest_event_moment');
        $this->addSql('DROP TABLE IF EXISTS event_moment');
    }
} 