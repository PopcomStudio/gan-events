<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220516082441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE time_slot (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, INDEX IDX_1B3294A71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE time_slot ADD CONSTRAINT FK_1B3294A71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE workshop ADD description LONGTEXT DEFAULT NULL, DROP time_slot');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE time_slot');
        $this->addSql('ALTER TABLE workshop ADD time_slot VARCHAR(255) DEFAULT NULL, DROP description');
    }
}
