<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220426115449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB21FB8D185');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA1FB8D185');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A351FB8D185');
        $this->addSql('ALTER TABLE view DROP FOREIGN KEY FK_FEFDAB8E1FB8D185');
        $this->addSql('CREATE TABLE sender (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, allocated_tickets INT DEFAULT NULL, accompanying_persons INT DEFAULT NULL, prospects INT DEFAULT NULL, overbooking INT DEFAULT NULL, autonomy_on_emails TINYINT(1) DEFAULT NULL, autonomy_on_schedule TINYINT(1) NOT NULL, email VARCHAR(255) DEFAULT NULL, plural TINYINT(1) DEFAULT NULL, INDEX IDX_5F004ACFA76ED395 (user_id), INDEX IDX_5F004ACF71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sender ADD CONSTRAINT FK_5F004ACFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE sender ADD CONSTRAINT FK_5F004ACF71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('INSERT INTO sender SELECT * FROM host');
        $this->addSql('DROP TABLE host');
        $this->addSql('DROP INDEX IDX_515E0EB21FB8D185 ON email_schedule');
        $this->addSql('ALTER TABLE email_schedule CHANGE host_id sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB2F624B39D FOREIGN KEY (sender_id) REFERENCES sender (id)');
        $this->addSql('CREATE INDEX IDX_515E0EB2F624B39D ON email_schedule (sender_id)');
        $this->addSql('DROP INDEX IDX_9C0600CA1FB8D185 ON email_template');
        $this->addSql('ALTER TABLE email_template CHANGE host_id sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CAF624B39D FOREIGN KEY (sender_id) REFERENCES sender (id)');
        $this->addSql('CREATE INDEX IDX_9C0600CAF624B39D ON email_template (sender_id)');
        $this->addSql('DROP INDEX IDX_ACB79A351FB8D185 ON guest');
        $this->addSql('ALTER TABLE guest CHANGE host_id sender_id INT NOT NULL');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A35F624B39D FOREIGN KEY (sender_id) REFERENCES sender (id)');
        $this->addSql('CREATE INDEX IDX_ACB79A35F624B39D ON guest (sender_id)');
        $this->addSql('DROP INDEX IDX_FEFDAB8E1FB8D185 ON view');
        $this->addSql('ALTER TABLE view CHANGE host_id sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE view ADD CONSTRAINT FK_FEFDAB8EF624B39D FOREIGN KEY (sender_id) REFERENCES sender (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FEFDAB8EF624B39D ON view (sender_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB2F624B39D');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CAF624B39D');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A35F624B39D');
        $this->addSql('ALTER TABLE view DROP FOREIGN KEY FK_FEFDAB8EF624B39D');
        $this->addSql('CREATE TABLE host (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, allocated_tickets INT DEFAULT NULL, accompanying_persons INT DEFAULT NULL, prospects INT DEFAULT NULL, overbooking INT DEFAULT NULL, autonomy_on_emails TINYINT(1) DEFAULT NULL, autonomy_on_schedule TINYINT(1) NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, plural TINYINT(1) DEFAULT NULL, INDEX IDX_CF2713FD71F7E88B (event_id), INDEX IDX_CF2713FDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('INSERT INTO host SELECT * FROM sender');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FD71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE sender');
        $this->addSql('DROP INDEX IDX_515E0EB2F624B39D ON email_schedule');
        $this->addSql('ALTER TABLE email_schedule CHANGE sender_id host_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB21FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('CREATE INDEX IDX_515E0EB21FB8D185 ON email_schedule (host_id)');
        $this->addSql('DROP INDEX IDX_9C0600CAF624B39D ON email_template');
        $this->addSql('ALTER TABLE email_template CHANGE sender_id host_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA1FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('CREATE INDEX IDX_9C0600CA1FB8D185 ON email_template (host_id)');
        $this->addSql('DROP INDEX IDX_ACB79A35F624B39D ON guest');
        $this->addSql('ALTER TABLE guest CHANGE sender_id host_id INT NOT NULL');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A351FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('CREATE INDEX IDX_ACB79A351FB8D185 ON guest (host_id)');
        $this->addSql('DROP INDEX IDX_FEFDAB8EF624B39D ON view');
        $this->addSql('ALTER TABLE view CHANGE sender_id host_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE view ADD CONSTRAINT FK_FEFDAB8E1FB8D185 FOREIGN KEY (host_id) REFERENCES host (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FEFDAB8E1FB8D185 ON view (host_id)');
    }
}
