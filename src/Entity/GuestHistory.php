<?php

namespace App\Entity;

use App\Repository\GuestHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GuestHistoryRepository::class)
 */
class GuestHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=EmailSchedule::class, inversedBy="history")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $schedule;

    /**
     * @ORM\ManyToOne(targetEntity=Guest::class, inversedBy="history")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $guest;

    /**
     * @ORM\Column(type="datetime")
     */
    private $sendAt;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    public function __construct(Guest $guest, EmailSchedule $schedule)
    {
        $this->guest = $guest;
        $this->schedule = $schedule;
        $this->type = $schedule->getType();
        $this->sendAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): ?EmailSchedule
    {
        return $this->schedule;
    }

    public function setSchedule(?EmailSchedule $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getGuest(): ?Guest
    {
        return $this->guest;
    }

    public function setGuest(?Guest $guest): self
    {
        $this->guest = $guest;

        return $this;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->sendAt;
    }

    public function setSendAt(\DateTimeInterface $sendAt): self
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
