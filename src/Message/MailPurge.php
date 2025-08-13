<?php

namespace App\Message;

class MailPurge
{
    private int $eventId;
    private int $days;

    public function __construct( int $eventId,  int $days)
    {
        $this->eventId = $eventId;
        $this->days = $days;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getDays(): int
    {
        return $this->days;
    }
}