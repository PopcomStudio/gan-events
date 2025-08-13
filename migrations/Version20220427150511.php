<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427150511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE added_guest (id INT AUTO_INCREMENT NOT NULL, guest_id INT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) NOT NULL, INDEX IDX_53E19D959A4AA658 (guest_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE added_guest ADD CONSTRAINT FK_53E19D959A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id)');
        $this->addSql('ALTER TABLE guest ADD created_at DATETIME DEFAULT NULL, ADD type VARCHAR(100) DEFAULT NULL, ADD backup TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE guest SET created_at = NOW()');
        $this->addSql('UPDATE guest SET status="pending" WHERE status="guest"');
        $this->addSql('UPDATE guest SET status="declined" WHERE status="decline"');
        $this->addSql('UPDATE guest SET status="accepted" WHERE status="accept"');
        $this->addSql('UPDATE guest SET status="participated" WHERE status="participe"');
        $this->addSql('UPDATE guest SET status="registered" WHERE status="subscribe"');
        $this->addSql('UPDATE guest SET type="guest" WHERE prospect=0 OR prospect IS NULL');
        $this->addSql('UPDATE guest SET type="prospect" WHERE prospect=1');
        $this->addSql('DELETE FROM guest WHERE status="backup" AND parent_id IS NULL');
        $this->addSql('UPDATE guest SET backup=0 WHERE backup IS NULL');
        $this->addSql('UPDATE guest SET backup=1, status="guest" WHERE status="backup"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE added_guest');
        $this->addSql('ALTER TABLE guest DROP created_at, DROP type, DROP backup');
    }
}
