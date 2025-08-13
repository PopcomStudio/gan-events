<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619104142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    $this->addSql(<<<'SQL'
        ALTER TABLE event_moment DROP FOREIGN KEY FK_715FFF071F7E88B
    SQL);
    $this->addSql(<<<'SQL'
        ALTER TABLE event_moment ADD CONSTRAINT FK_715FFF071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE
    SQL);
    $this->addSql(<<<'SQL'
        ALTER TABLE guest_moment_choice ADD CONSTRAINT FK_FCAAAB1B9A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE
    SQL);
}

public function down(Schema $schema): void
{
    $this->addSql(<<<'SQL'
        ALTER TABLE guest_moment_choice ADD CONSTRAINT FK_FCAAAB1B9A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id)
    SQL);
    $this->addSql(<<<'SQL'
        ALTER TABLE event_moment DROP FOREIGN KEY FK_715FFF071F7E88B
    SQL);
    $this->addSql(<<<'SQL'
        ALTER TABLE event_moment ADD CONSTRAINT FK_715FFF071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
    SQL);
}
}
