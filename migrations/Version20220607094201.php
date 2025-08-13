<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220607094201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD parent_id INT DEFAULT NULL, CHANGE begin_at begin_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7727ACA70 FOREIGN KEY (parent_id) REFERENCES event (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7727ACA70 ON event (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7727ACA70');
        $this->addSql('DROP INDEX IDX_3BAE0AA7727ACA70 ON event');
        $this->addSql('ALTER TABLE event DROP parent_id, CHANGE begin_at begin_at DATETIME NOT NULL');
    }
}
