<?php

namespace App\Entity;

use App\Repository\EmailScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailScheduleRepository::class)
 */
class EmailSchedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="schedules")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity=Sender::class, inversedBy="schedules")
     */
    private $sender;

    /**
     * @ORM\Column(type="datetime")
     */
    private $sendAt;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=EmailTemplate::class)
     */
    private $template;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $signature;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $processedAt;

    /**
     * @ORM\OneToMany(targetEntity=GuestHistory::class, mappedBy="schedule", orphanRemoval=true)
     */
    private $history;

    /**
     * @ORM\Column(type="boolean")
     */
    private $onlyNew;

    private $templateOrInput;

    public function __construct()
    {
        $this->history = new ArrayCollection();
        $this->onlyNew = false;
    }

    /* ----------------------------------- */

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDisplayType(): ?string
    {
        return $this->type ? EmailTemplate::getTypes()[$this->type] : null;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature;

        return $this;
    }

    public function getDisplaySignature(?Guest $guest = null): ?string
    {
        return $this->signature
            ? str_replace('%%EXPEDITEUR%%', $guest->getSender()->getDisplayName(), $this->signature)
            : $guest->getSender()->getDisplayName()
        ;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getTemplateOrInput(): ?bool
    {
        if (is_null($this->templateOrInput)) $this->templateOrInput = ! is_null($this->template);

        return $this->templateOrInput;
    }

    public function setTemplateOrInput(bool $templateOrInput): self
    {
        $this->templateOrInput = $templateOrInput;

        return $this;
    }

    /**
     * @return Collection|GuestHistory[]
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function addHistory(GuestHistory $history): self
    {
        if (!$this->history->contains($history)) {
            $this->history[] = $history;
            $history->setSchedule($this);
        }

        return $this;
    }

    public function removeHistory(GuestHistory $history): self
    {
        if ($this->history->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getSchedule() === $this) {
                $history->setSchedule(null);
            }
        }

        return $this;
    }

    public function getOnlyNew(): ?bool
    {
        return $this->onlyNew;
    }

    public function setOnlyNew(bool $onlyNew): self
    {
        $this->onlyNew = $onlyNew;

        return $this;
    }
}
