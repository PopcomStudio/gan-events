<?php

namespace App\MessageHandler;

use App\Entity\EmailSchedule;
use App\Entity\Guest;
use App\Entity\GuestHistory;
use App\Message\MailNotification;
use App\Service\Mailer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MailNotificationHandler implements MessageHandlerInterface
{
    /** @var ObjectManager $om */
    private $om;

    /** @var Mailer $mailer */
    private $mailer;

    public function __construct(ObjectManager $om, Mailer $mailer)
    {
        $this->om = $om;
        $this->mailer = $mailer;
    }

    public function __invoke(MailNotification $notification)
    {
        $guest = $this->om->getRepository(Guest::class)->find($notification->getGuestId());
        $schedule = $this->om->getRepository(EmailSchedule::class)->find($notification->getScheduleId());

        if ($guest && $schedule) {

            $history = new GuestHistory($guest, $schedule);

            $this->mailer->sendEventMessage($guest, $schedule);

            $this->om->persist($history);
            $this->om->flush();
        }
    }
}
