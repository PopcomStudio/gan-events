<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetPasswordTokenCommand extends Command
{
    protected static $defaultName = 'app:reset-password-token';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Réinitialise le token de réinitialisation de mot de passe pour un utilisateur')
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error('Utilisateur non trouvé avec cet email.');
            return Command::FAILURE;
        }

        // Supprimer tous les tokens existants pour cet utilisateur
        $connection = $this->entityManager->getConnection();
        $sql = 'DELETE FROM reset_password_request WHERE user_id = :userId';
        $stmt = $connection->prepare($sql);
        $stmt->execute(['userId' => $user->getId()]);

        $io->success('Le token de réinitialisation a été supprimé. Vous pouvez maintenant demander un nouveau lien de réinitialisation.');

        return Command::SUCCESS;
    }
} 