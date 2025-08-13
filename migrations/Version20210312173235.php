<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210312173235 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, file_original_name VARCHAR(255) NOT NULL, file_mime_type VARCHAR(255) NOT NULL, file_size INT NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_schedule (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, host_id INT DEFAULT NULL, template_id INT DEFAULT NULL, send_at DATETIME NOT NULL, type VARCHAR(191) NOT NULL, subject VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, signature VARCHAR(255) DEFAULT NULL, processed_at DATETIME DEFAULT NULL, only_new TINYINT(1) NOT NULL, INDEX IDX_515E0EB271F7E88B (event_id), INDEX IDX_515E0EB21FB8D185 (host_id), INDEX IDX_515E0EB25DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, host_id INT DEFAULT NULL, event_id INT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, type VARCHAR(100) NOT NULL, signature LONGTEXT DEFAULT NULL, INDEX IDX_9C0600CA1FB8D185 (host_id), INDEX IDX_9C0600CA71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, main_host_id INT DEFAULT NULL, visual_id INT DEFAULT NULL, ticket_visual_id INT DEFAULT NULL, email_visual_id INT DEFAULT NULL, email_reminder_visual_id INT DEFAULT NULL, email_up_visual_id INT DEFAULT NULL, email_thanks_visual_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, guest_type VARCHAR(100) NOT NULL, begin_at DATETIME NOT NULL, foundation TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, address LONGTEXT DEFAULT NULL, target VARCHAR(255) DEFAULT NULL, slug VARCHAR(191) NOT NULL, total_tickets INT DEFAULT NULL, lat NUMERIC(10, 8) DEFAULT NULL, lng NUMERIC(11, 8) DEFAULT NULL, UNIQUE INDEX UNIQ_3BAE0AA7989D9B62 (slug), INDEX IDX_3BAE0AA77E3C61F9 (owner_id), INDEX IDX_3BAE0AA7EBF3786C (main_host_id), INDEX IDX_3BAE0AA760D949C1 (visual_id), INDEX IDX_3BAE0AA7FAE3938B (ticket_visual_id), INDEX IDX_3BAE0AA76FD2D6EA (email_visual_id), INDEX IDX_3BAE0AA77C8250BB (email_reminder_visual_id), INDEX IDX_3BAE0AA7A2C20481 (email_up_visual_id), INDEX IDX_3BAE0AA7716A11F7 (email_thanks_visual_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE guest (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, host_id INT NOT NULL, parent_id INT DEFAULT NULL, gender VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, siret VARCHAR(255) DEFAULT NULL, uuid VARCHAR(150) DEFAULT NULL, status VARCHAR(100) DEFAULT NULL, prospect TINYINT(1) DEFAULT NULL, INDEX IDX_ACB79A3571F7E88B (event_id), INDEX IDX_ACB79A351FB8D185 (host_id), INDEX IDX_ACB79A35727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE guest_history (id INT AUTO_INCREMENT NOT NULL, schedule_id INT NOT NULL, guest_id INT NOT NULL, send_at DATETIME NOT NULL, INDEX IDX_677FB407A40BC2D5 (schedule_id), INDEX IDX_677FB4079A4AA658 (guest_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE host (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, allocated_tickets INT DEFAULT NULL, accompanying_persons INT DEFAULT NULL, prospects INT DEFAULT NULL, overbooking INT DEFAULT NULL, autonomy_on_emails TINYINT(1) DEFAULT NULL, autonomy_on_schedule TINYINT(1) NOT NULL, INDEX IDX_CF2713FDA76ED395 (user_id), INDEX IDX_CF2713FD71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE optout (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, registered_at DATETIME DEFAULT NULL, agreed_terms_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB271F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB21FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB25DA0FB8 FOREIGN KEY (template_id) REFERENCES email_template (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA1FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA77E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7EBF3786C FOREIGN KEY (main_host_id) REFERENCES host (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA760D949C1 FOREIGN KEY (visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7FAE3938B FOREIGN KEY (ticket_visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76FD2D6EA FOREIGN KEY (email_visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA77C8250BB FOREIGN KEY (email_reminder_visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A2C20481 FOREIGN KEY (email_up_visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7716A11F7 FOREIGN KEY (email_thanks_visual_id) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A3571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A351FB8D185 FOREIGN KEY (host_id) REFERENCES host (id)');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A35727ACA70 FOREIGN KEY (parent_id) REFERENCES guest (id)');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB407A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES email_schedule (id)');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB4079A4AA658 FOREIGN KEY (guest_id) REFERENCES guest (id)');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FD71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('INSERT INTO `user` (`id`, `email`, `roles`, `password`, `first_name`, `last_name`, `registered_at`, `agreed_terms_at`) VALUES(1, \'remi@popcom.me\', \'[\"ROLE_ADMIN\"]\', \'$argon2id$v=19$m=65536,t=4,p=1$OWFiaVBOdU9qa0lOaXdUYg$eTwGMPOydXDSUruMGioPAh7hUKrDsNycO+3QShHaxj8\', \'Rémi\', \'Sanchez\', \'2021-02-07 08:58:27\', \'2021-02-07 08:58:27\')');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA760D949C1');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7FAE3938B');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA76FD2D6EA');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA77C8250BB');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A2C20481');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7716A11F7');
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB407A40BC2D5');
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB25DA0FB8');
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB271F7E88B');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA71F7E88B');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A3571F7E88B');
        $this->addSql('ALTER TABLE host DROP FOREIGN KEY FK_CF2713FD71F7E88B');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A35727ACA70');
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB4079A4AA658');
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB21FB8D185');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA1FB8D185');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7EBF3786C');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A351FB8D185');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA77E3C61F9');
        $this->addSql('ALTER TABLE host DROP FOREIGN KEY FK_CF2713FDA76ED395');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE email_schedule');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE guest');
        $this->addSql('DROP TABLE guest_history');
        $this->addSql('DROP TABLE host');
        $this->addSql('DROP TABLE optout');
        $this->addSql('DROP TABLE `user`');
    }
}
