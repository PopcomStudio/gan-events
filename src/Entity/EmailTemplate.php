<?php

namespace App\Entity;

use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailTemplateRepository::class)
 */
class EmailTemplate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    private static $types = [
        'invitation' => 'Invitation',
        'up' => 'Relance',
        'reminder' => 'Rappel',
        'thanks' => 'Remerciement',
				'everyone' => "Informations générales",
    ];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Sender::class, inversedBy="emailTemplates")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="emailTemplates")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $event;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $signature;

    /* ------------------------------------ */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDisplayType(): ?string
    {
        return $this->type ? self::$types[$this->type] : null;
    }

    public static function getTypes(): array
    {
        return self::$types;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function getDisplaySignature(?Guest $guest = null): ?string
    {
        return $this->signature
            ? str_replace('%%EXPEDITEUR%%', $guest->getSender()->getDisplayName(), $this->signature)
            : $guest->getSender()->getDisplayName()
            ;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature;

        return $this;
    }
}
