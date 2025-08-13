<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240322000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne cancelled_at à la table guest';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest ADD cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guest DROP cancelled_at');
    }
} 