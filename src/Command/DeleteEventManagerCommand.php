<?php

namespace App\Command;

use App\Entity\Event;
use App\Message\MailPurge;
use DateInterval;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class DeleteEventManagerCommand extends Command
{
    /** @var ObjectManager $om */
    private $om;
    /** @var MessageBusInterface $bus */
    private $bus;

    protected static $defaultName = 'app:delete-event:manager';
    protected static $defaultDescription = 'command send email manager: delete event';

    public function __construct(ObjectManager $om, MessageBusInterface $bus)
    {
        parent::__construct(null);

        $this->om = $om;
        $this->bus = $bus;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $day = new \DateTime();
        $date2yearsAgo = $day->sub(new DateInterval('P2Y'));
        $after2YearsAgo = (clone $date2yearsAgo);

        // define deadlines
        $deadlines = [
            90 => (clone $date2yearsAgo)->add( new DateInterval('P90D') ),
            30 => (clone $date2yearsAgo)->add( new DateInterval('P30D') ),
            7  => (clone $date2yearsAgo)->add( new DateInterval('P7D') ),
            3  => (clone $date2yearsAgo)->add( new DateInterval('P3D') ),
        ];

        // get events
        $events = $this->om->getRepository(Event::class)->findByDeadlines($deadlines, $after2YearsAgo);

        // si j'ai bien des évènements concernés par le rappel
        if ( !empty($events) ) {

            $io->info(count($events).' événement(s) trouvé(s).');

            /** @var Event $event */
            foreach ($events as $event) {

                $days = null;
                $dateEvent = $event->getFinishAt() ?: $event->getBeginAt();
                $dateEvent = $dateEvent->format('Y-m-d');

                foreach ($deadlines as $key => $deadline) {

                    if ($dateEvent == $deadline->format('Y-m-d')) {

                        $days = $key;
                        break;
                    }
                }

                if ($dateEvent <= $after2YearsAgo->format('Y-m-d')) $days = 0;

                if ( ! is_null($days) ) {

                    $this->bus->dispatch(new MailPurge($event->getId(), $days));
                    $io->success($event->getName().' traité.');
                }
            }
            
        } else {

            $io->warning('Rien à faire :)');
        }

        return 0;
    }
}
