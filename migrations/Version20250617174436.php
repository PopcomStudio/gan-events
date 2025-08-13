<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617174436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment DROP FOREIGN KEY FK_EVENT_MOMENT_EVENT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment ADD max_guests INT DEFAULT NULL, DROP created_at, CHANGE type type VARCHAR(100) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment ADD CONSTRAINT FK_715FFF071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment RENAME INDEX idx_event_moment_event TO IDX_715FFF071F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE guest CHANGE registered_at registered_at DATETIME DEFAULT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE guest_event_moment RENAME INDEX idx_guest_event_moment_guest TO IDX_42428479A4AA658
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE guest_event_moment RENAME INDEX idx_guest_event_moment_moment TO IDX_42428472D7CAF7E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `option` CHANGE data data JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sender CHANGE stat stat JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT '(DC2Type:json)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE guest_event_moment RENAME INDEX idx_42428472d7caf7e TO IDX_GUEST_EVENT_MOMENT_MOMENT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE guest_event_moment RENAME INDEX idx_42428479a4aa658 TO IDX_GUEST_EVENT_MOMENT_GUEST
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sender CHANGE stat stat JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE guest CHANGE registered_at registered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment DROP FOREIGN KEY FK_715FFF071F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment ADD created_at DATETIME NOT NULL, DROP max_guests, CHANGE type type VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment ADD CONSTRAINT FK_EVENT_MOMENT_EVENT FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_moment RENAME INDEX idx_715fff071f7e88b TO IDX_EVENT_MOMENT_EVENT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` CHANGE roles roles JSON NOT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `option` CHANGE data data JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
    }
}
