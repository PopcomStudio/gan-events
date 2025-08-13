<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220613150855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB4079A4AA658');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB4079A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB4079A4AA658');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB4079A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id)');
    }
}
