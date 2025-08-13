<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220121142333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD movie_poster_id INT DEFAULT NULL, ADD movie_title VARCHAR(255) DEFAULT NULL, ADD movie_genres VARCHAR(255) DEFAULT NULL, ADD movie_directed_by VARCHAR(255) DEFAULT NULL, ADD movie_starred_by VARCHAR(255) DEFAULT NULL, ADD movie_countries VARCHAR(255) DEFAULT NULL, ADD movie_running_time INT DEFAULT NULL, ADD movie_released_at DATETIME DEFAULT NULL, ADD movie_awards VARCHAR(255) DEFAULT NULL, ADD movie_overview LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7C8636503 FOREIGN KEY (movie_poster_id) REFERENCES attachment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7C8636503 ON event (movie_poster_id)');
        $this->addSql('ALTER TABLE event_manager DROP FOREIGN KEY FK_92589AE271F7E88B');
        $this->addSql('ALTER TABLE event_manager DROP FOREIGN KEY FK_92589AE2A76ED395');
        $this->addSql('DROP INDEX idx_92589ae271f7e88b ON event_manager');
        $this->addSql('CREATE INDEX IDX_7F738C1671F7E88B ON event_manager (event_id)');
        $this->addSql('DROP INDEX idx_92589ae2a76ed395 ON event_manager');
        $this->addSql('CREATE INDEX IDX_7F738C16A76ED395 ON event_manager (user_id)');
        $this->addSql('ALTER TABLE event_manager ADD CONSTRAINT FK_92589AE271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_manager ADD CONSTRAINT FK_92589AE2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7C8636503');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA7C8636503 ON event');
        $this->addSql('ALTER TABLE event DROP movie_poster_id, DROP movie_title, DROP movie_genres, DROP movie_directed_by, DROP movie_starred_by, DROP movie_countries, DROP movie_running_time, DROP movie_released_at, DROP movie_awards, DROP movie_overview');
        $this->addSql('ALTER TABLE event_manager DROP FOREIGN KEY FK_7F738C1671F7E88B');
        $this->addSql('ALTER TABLE event_manager DROP FOREIGN KEY FK_7F738C16A76ED395');
        $this->addSql('DROP INDEX idx_7f738c16a76ed395 ON event_manager');
        $this->addSql('CREATE INDEX IDX_92589AE2A76ED395 ON event_manager (user_id)');
        $this->addSql('DROP INDEX idx_7f738c1671f7e88b ON event_manager');
        $this->addSql('CREATE INDEX IDX_92589AE271F7E88B ON event_manager (event_id)');
        $this->addSql('ALTER TABLE event_manager ADD CONSTRAINT FK_7F738C1671F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_manager ADD CONSTRAINT FK_7F738C16A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
