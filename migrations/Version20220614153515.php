<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220614153515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB271F7E88B');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA71F7E88B');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CAF624B39D');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CAF624B39D FOREIGN KEY (sender_id) REFERENCES sender (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A35F624B39D');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A35F624B39D FOREIGN KEY (sender_id) REFERENCES sender (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB407A40BC2D5');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB407A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES email_schedule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sender DROP FOREIGN KEY FK_5F004ACF71F7E88B');
        $this->addSql('ALTER TABLE sender ADD CONSTRAINT FK_5F004ACF71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_schedule DROP FOREIGN KEY FK_515E0EB271F7E88B');
        $this->addSql('ALTER TABLE email_schedule ADD CONSTRAINT FK_515E0EB271F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CAF624B39D');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA71F7E88B');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CAF624B39D FOREIGN KEY (sender_id) REFERENCES sender (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE guest DROP FOREIGN KEY FK_ACB79A35F624B39D');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A35F624B39D FOREIGN KEY (sender_id) REFERENCES sender (id)');
        $this->addSql('ALTER TABLE guest_history DROP FOREIGN KEY FK_677FB407A40BC2D5');
        $this->addSql('ALTER TABLE guest_history ADD CONSTRAINT FK_677FB407A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES email_schedule (id)');
        $this->addSql('ALTER TABLE sender DROP FOREIGN KEY FK_5F004ACF71F7E88B');
        $this->addSql('ALTER TABLE sender ADD CONSTRAINT FK_5F004ACF71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }
}
