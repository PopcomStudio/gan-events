<?php

namespace App\Entity;

use App\Repository\SenderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SenderRepository::class)
 */
class Sender
{
    public const GUEST_TYPE_DEFAULT    = 'all';
    public const GUEST_TYPE_PRO        = 'pro';
    public const GUEST_TYPE_INDIVIDUAL = 'particulier';
    public const GUEST_TYPE_AGENT      = 'agent';
    public const GUEST_TYPE_INTERNAL   = 'internal';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="senders")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="senders")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $event;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $allocatedTickets;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sidekicks;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prospects;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $overbooking;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $autonomyOnEmails;

    /**
     * @ORM\Column(type="boolean")
     */
    private $autonomyOnSchedule;

    /**
     * @ORM\OneToMany(targetEntity=Guest::class, mappedBy="sender", orphanRemoval=true)
     */
    private $guests;

    /**
     * @ORM\OneToMany(targetEntity=EmailTemplate::class, mappedBy="sender")
     */
    private $emailTemplates;

    /**
     * @ORM\OneToMany(targetEntity=EmailSchedule::class, mappedBy="sender")
     */
    private $schedules;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $plural;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $legalNoticeSender;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $stat = [];

    /**
     * @Assert\Choice({"all", "pro", "particulier", "agent", "internal"})
     * @ORM\Column(type="string", length=100)
     */
    private $guestType;

    public function __construct()
    {
        $this->guests = new ArrayCollection();
        $this->emailTemplates = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->autonomyOnSchedule = true;
        $this->autonomyOnEmails = true;
        $this->guestType = self::GUEST_TYPE_DEFAULT;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDisplayName(): ?string
    {
        return $this->name ?: ($this->user ? $this->user->getDisplayName() : '');
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAllocatedTickets(): ?int
    {
        return $this->allocatedTickets;
    }

    public function setAllocatedTickets(?int $allocatedTickets): self
    {
        $this->allocatedTickets = $allocatedTickets;

        return $this;
    }

    public function getSidekicks(): ?int
    {
        return $this->sidekicks;
    }

    public function setSidekicks(?int $sidekicks): self
    {
        $this->sidekicks = $sidekicks;

        return $this;
    }

    public function getProspects(): ?int
    {
        return $this->prospects;
    }

    public function setProspects(?int $prospects): self
    {
        $this->prospects = $prospects;

        return $this;
    }

    public function getOverbooking(): ?int
    {
        return $this->overbooking;
    }

    public function setOverbooking(?int $overbooking): self
    {
        $this->overbooking = $overbooking;

        return $this;
    }

    public function getAutonomyOnEmails(): ?bool
    {
        return $this->autonomyOnEmails;
    }

    public function setAutonomyOnEmails(?bool $autonomyOnEmails): self
    {
        $this->autonomyOnEmails = $autonomyOnEmails;

        return $this;
    }

    public function getAutonomyOnSchedule(): ?bool
    {
        return $this->autonomyOnSchedule;
    }

    public function setAutonomyOnSchedule(bool $autonomyOnSchedule): self
    {
        $this->autonomyOnSchedule = $autonomyOnSchedule;

        return $this;
    }

    /**
     * @return Collection|Guest[]
     */
    public function getGuests($status = null): Collection
    {
        $criteria = new Criteria();

        if ($status === null) {

            $criteria->where($criteria::expr()->neq('status', 'backup'));
        }

        return $this->guests->matching($criteria);
    }

    public function addGuest(Guest $guest): self
    {
        if (!$this->guests->contains($guest)) {
            $this->guests[] = $guest;
            $guest->setSender($this);
        }

        return $this;
    }

    public function removeGuest(Guest $guest): self
    {
        if ($this->guests->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getSender() === $this) {
                $guest->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|EmailTemplate[]
     */
    public function getEmailTemplates(): Collection
    {
        return $this->emailTemplates;
    }

    public function addEmailTemplate(EmailTemplate $emailTemplate): self
    {
        if (!$this->emailTemplates->contains($emailTemplate)) {
            $this->emailTemplates[] = $emailTemplate;
            $emailTemplate->setSender($this);
        }

        return $this;
    }

    public function removeEmailTemplate(EmailTemplate $emailTemplate): self
    {
        if ($this->emailTemplates->removeElement($emailTemplate)) {
            // set the owning side to null (unless already changed)
            if ($emailTemplate->getSender() === $this) {
                $emailTemplate->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|EmailSchedule[]
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(EmailSchedule $schedule): self
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules[] = $schedule;
            $schedule->setSender($this);
        }

        return $this;
    }

    public function removeSchedule(EmailSchedule $schedule): self
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getSender() === $this) {
                $schedule->setSender(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPublicEmail(): ?string
    {
        return $this->email ?: ($this->user ? $this->user->getEmail() : '');
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPlural(): ?bool
    {
        return $this->plural;
    }

    public function setPlural(?bool $plural): self
    {
        $this->plural = $plural;

        return $this;
    }

    public function getLegalNoticeSender(): ?string
    {
        return $this->legalNoticeSender;
    }

    public function setLegalNoticeSender(?string $legalNoticeSender): self
    {
        $this->legalNoticeSender = $legalNoticeSender;

        return $this;
    }

    public function getStat(): ?array
    {
        return $this->stat;
    }

    public function setStat(?array $stat): self
    {
        $this->stat = $stat;

        return $this;
    }

    public function getGuestType(): ?string
    {
        return $this->guestType;
    }

    public static function getGuestTypes(): array
    {
        return [
            self::GUEST_TYPE_DEFAULT => 'Externe',
            self::GUEST_TYPE_PRO => 'Professionnel',
            self::GUEST_TYPE_INDIVIDUAL => 'Particulier',
            self::GUEST_TYPE_AGENT => 'Agent',
            self::GUEST_TYPE_INTERNAL => 'Interne',
        ];
    }

    public function getDisplayGuestType(): ?string
    {
        return $this->guestType && isset(self::getGuestTypes()[$this->guestType]) ? self::getGuestTypes()[$this->guestType] : null;
    }

    public function setGuestType(string $guestType): self
    {
        $this->guestType = $guestType;

        return $this;
    }
}
