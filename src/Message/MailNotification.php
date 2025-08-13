<?php

namespace App\Message;

class MailNotification
{
    private int $scheduleId;
    private int $guestId;

    public function __construct(int $scheduleId, int $guestId)
    {
        $this->scheduleId = $scheduleId;
        $this->guestId = $guestId;
    }

    public function getScheduleId(): int
    {
        return $this->scheduleId;
    }

    public function getGuestId(): int
    {
        return $this->guestId;
    }
}