<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240322000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table de jointure guest_moment pour les moments clés';
    }

    public function up(Schema $schema): void
    {
        // Cette migration est obsolète car la table de jointure est créée dans Version20240519165651.php
    }

    public function down(Schema $schema): void
    {
        // Cette migration est obsolète car la table de jointure est créée dans Version20240519165651.php
    }
} 