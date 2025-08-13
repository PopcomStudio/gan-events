<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630123710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `option` (name, display_name) VALUES (\'internalEmailLegal\', \'Mentions légales des emails internes\'), (\'agentEmaillegal\', \'Mentions légales des emails aux agents\')');
        $this->addSql('UPDATE `option` SET display_name = \'Mentions légales des emails externes\' WHERE name = \'emailLegal\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM `option` WHERE name IN (\'internalEmailLegal\', \'agentEmaillegal\')');
    }
}
