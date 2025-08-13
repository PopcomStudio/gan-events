<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test-email';
    private $mailer;
    private $transport;

    public function __construct(MailerInterface $mailer, TransportInterface $transport)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->transport = $transport;
    }

    protected function configure()
    {
        $this
            ->setDescription('Test l\'envoi d\'emails')
            ->setHelp('Cette commande permet de tester la configuration des emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Configuration SMTP :');
        $output->writeln('Host : groot.popcom.me');
        $output->writeln('Port : 465 (SSL)');
        $output->writeln('Username : support@popcom.me');
        
        $email = (new Email())
            ->from('support@popcom.me')
            ->to('support@popcom.me')
            ->subject('Test d\'envoi d\'email')
            ->text('Ceci est un email de test pour vérifier la configuration SMTP.')
            ->html('<p>Ceci est un email de test pour vérifier la configuration SMTP.</p>');

        try {
            $this->mailer->send($email);
            $output->writeln('<info>Email envoyé avec succès !</info>');
            
            // Vérifier les logs
            $output->writeln('Vérifiez les logs dans : var/log/dev.log');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur lors de l\'envoi de l\'email : ' . $e->getMessage() . '</error>');
            $output->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 