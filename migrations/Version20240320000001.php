<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ participationType pour les événements standard plus moments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest ADD participation_type VARCHAR(20) DEFAULT NULL');

        // Vérifier si les colonnes existent avant de les supprimer
        $table = $schema->getTable('event');
        
        if ($table->hasColumn('soiree_begin_at')) {
            $this->addSql('ALTER TABLE event DROP COLUMN soiree_begin_at');
        }
        if ($table->hasColumn('soiree_finish_at')) {
            $this->addSql('ALTER TABLE event DROP COLUMN soiree_finish_at');
        }
        if ($table->hasColumn('soiree_description')) {
            $this->addSql('ALTER TABLE event DROP COLUMN soiree_description');
        }
        if ($table->hasColumn('soiree_address')) {
            $this->addSql('ALTER TABLE event DROP COLUMN soiree_address');
        }
        if ($table->hasColumn('soiree_max_guests')) {
            $this->addSql('ALTER TABLE event DROP COLUMN soiree_max_guests');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest DROP participation_type');
    }
} 