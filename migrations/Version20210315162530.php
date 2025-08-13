<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315162530 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD logo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7F98F144A FOREIGN KEY (logo_id) REFERENCES attachment (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7F98F144A ON event (logo_id)');
        $this->addSql('ALTER TABLE guest ADD golf_license VARCHAR(9) DEFAULT NULL, ADD golf_index NUMERIC(5, 2) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7F98F144A');
        $this->addSql('DROP INDEX IDX_3BAE0AA7F98F144A ON event');
        $this->addSql('ALTER TABLE event DROP logo_id');
        $this->addSql('ALTER TABLE guest DROP golf_license, DROP golf_index');
    }
}
