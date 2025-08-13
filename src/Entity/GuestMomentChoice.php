<?php

namespace App\Entity;

use App\Repository\GuestMomentChoiceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GuestMomentChoiceRepository::class)
 */
class GuestMomentChoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Guest::class, inversedBy="momentChoices")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $guest;

    /**
     * @ORM\ManyToOne(targetEntity=EventMoment::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEvent(): ?EventMoment
    {
        return $this->event;
    }

    public function setEvent(?EventMoment $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
} 