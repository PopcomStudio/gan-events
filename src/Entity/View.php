<?php

namespace App\Entity;

use App\Repository\ViewRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ViewRepository::class)
 */
class View
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?User $user;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="views")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?Event $event;

    /**
     * @ORM\ManyToOne(targetEntity=Sender::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private ?Sender $sender;

    public function __construct(?User $user, Event $event, ?Sender $sender = null)
    {
        $this->user = $user;
        $this->event = $event;
        $this->sender = $sender;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getSender(): ?Sender
    {
        return $this->sender;
    }

    public function setSender(?Sender $sender): self
    {
        $this->sender = $sender;

        return $this;
    }
}
