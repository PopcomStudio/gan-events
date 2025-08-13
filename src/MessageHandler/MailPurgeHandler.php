<?php

namespace App\MessageHandler;

use App\Entity\Event;
use App\Message\MailPurge;
use App\Repository\GuestRepository;
use App\Service\Mailer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MailPurgeHandler implements MessageHandlerInterface {

    private ObjectManager $om;
    private Mailer $mailer;

    public function __construct(ObjectManager $om, Mailer $mailer)
    {
        $this->om = $om;
        $this->mailer = $mailer;
    }

    public function __invoke(MailPurge $purge)
    {
        /** @var Event|null $event */
        $event = $this->om->getRepository(Event::class)->find($purge->getEventId());
        $days = $purge->getDays();

        // Si l'événement existe
        if ($event) {

            // Si days vaut 0, supprimer l'événement
            if ($days === 0) {

                $guestRepository = $this->om->getRepository(GuestRepository::class);

                foreach ($event->getSenders() as $sender) {

                    $stat = [
                        'totalContactees' => $guestRepository->getTotalContacted($sender),
                        'totalTickets'    => $guestRepository->getTotalTickets($sender),
                        'totalGuests'     => $guestRepository->getTotalContacts($sender),
                        'totalReplies'    => $guestRepository->getTotalSupplies($sender),
                    ];

                    $sender->setStat($stat);
                    $this->om->persist($sender);
                }

                // Passer la date du jour à l'évènement pour savoir qu'il a été clôturé
                $event->setClosedAt(new \DateTimeImmutable());

                // Archiver l'événement
                $event->toggleArchived();

                // Supprimer les Guests
                foreach ($event->getGuests() as $guest){

                    $this->om->remove($guest);
                }

                $this->om->persist($event);
                $this->om->flush();
            }

            // Envoyer un email
            $this->mailer->sendRappelDeleteEvent($event, $days);
        }
    }
}