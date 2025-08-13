<?php

namespace App\Command;

use App\Entity\EmailSchedule;
use App\Entity\Guest;
use App\Message\MailNotification;
use App\Repository\GuestRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class EventSendCommand extends Command
{
    /** @var ObjectManager $om */
    private $om;

    /** @var MessageBusInterface $bus */
    private $bus;

    protected static $defaultName = 'app:schedules:prepare';

    public function __construct(ObjectManager $om, MessageBusInterface $bus)
    {
        parent::__construct(null);

        $this->om = $om;
        $this->bus = $bus;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send emails to guests every 5 minutes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $schedules = $this->om->getRepository(EmailSchedule::class)->findToProcess();

        /** @var GuestRepository $guestRepository */
        $guestRepository = $this->om->getRepository(Guest::class);

        /** @var EmailSchedule $schedule */
        foreach ($schedules as $schedule) {

            $schedule->setProcessedAt(new \DateTime());
            $this->om->persist($schedule);

            // Récupérer les contacts et les mettre en fil d'attente

            $guests = $guestRepository->findForSchedule($schedule);

            foreach ($guests as $guest) {

                $this->bus->dispatch(new MailNotification($schedule->getId(), $guest->getId()));
            }
        }

        if (!empty($schedules)) {

            $this->om->flush();

            $total = count($schedules);
            $msg = $total === 1 ? '1 traitement réalisé.' : $total .' traitements réalisés.';

            $io->success($msg);

        } else {

            $io->warning('Rien a faire :)');
        }



        return 0;
    }
}
