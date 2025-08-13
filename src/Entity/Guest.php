<?php

namespace App\Entity;

use App\Repository\GuestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GuestRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Guest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    const NAMESPACE = 'd00d440f-6a18-46dc-a005-afeeb3bab485';
    public const STATUS_PENDING      = 'pending';
    public const STATUS_REGISTERED   = 'registered';
    public const STATUS_DECLINED     = 'declined';
    public const STATUS_PARTICIPATED = 'participated';
    public const STATUS_SWITCHED     = 'switched';
    public const TYPE_GUEST    = 'guest';
    public const TYPE_PROSPECT = 'prospect';
    public const TYPE_SIDEKICK = 'sidekick';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siret;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="guests")
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity=Sender::class, inversedBy="guests")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $sender;

    /**
     * @ORM\OneToMany(targetEntity=GuestHistory::class, mappedBy="guest")
     */
    private $history;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private $uuid;

    /**
     * pending, registered, refused, participated
     * @ORM\Column(type="string", length=100)
     */
    private $status;

    /**
     * guest, prospect
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Guest::class, inversedBy="children")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Guest::class, mappedBy="parent", orphanRemoval=true)
     */
    private $children;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     */
    private $golfLicense;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $golfIndex;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $backup = false;

    private $prospects;
    private $sidekicks;

    /**
     * @ORM\ManyToMany(targetEntity=WorkshopTimeSlot::class, inversedBy="guests", cascade={"persist"})
     */
    private $workshops;

    /**
     * @ORM\ManyToMany(targetEntity=EventMoment::class, inversedBy="guests", cascade={"persist"})
     * @ORM\JoinTable(name="guest_event_moment",
     *      joinColumns={@ORM\JoinColumn(name="guest_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_moment_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $moments;

    private $sendEmail = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $inspecteurCommercial;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $participationType;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $registeredAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $cancelledAt;

    /**
     * @ORM\OneToMany(targetEntity=GuestMomentChoice::class, mappedBy="guest", cascade={"persist", "remove"})
     */
    private $momentChoices;

    /* ----------------------------------- */

    public function __construct()
    {
        $this->type = self::TYPE_GUEST;
        $this->status = self::STATUS_PENDING;
        $this->history = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->prospects = new ArrayCollection();
        $this->sidekicks = new ArrayCollection();
        $this->workshops = new ArrayCollection();
        $this->moments = new ArrayCollection();
        $this->momentChoices = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
    }

    public function clearWorkshops(): self
    {
        $this->workshops = new ArrayCollection();

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt = null): self
    {
        if ($updatedAt === null) $updatedAt = new \DateTime();

        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getDisplayGender(): ?string
    {
        if ($this->gender === 'Monsieur') return 'M.';
        elseif ($this->gender === 'Madame') return 'Mme';

        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getDisplayName(): string
    {
        $displayName = '';
        $gender = $this->getDisplayGender();

        if ($gender) $displayName.= $gender;
        if ($displayName) $displayName.= ' ';
        if ($this->firstName) $displayName.= $this->firstName;
        if ($displayName) $displayName.= ' ';
        if ($this->lastName) $displayName.= $this->lastName;

        return $displayName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

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
            $history->setGuest($this);
        }

        return $this;
    }

    public function removeHistory(GuestHistory $history): self
    {
        if ($this->history->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getGuest() === $this) {
                $history->setGuest(null);
            }
        }

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getDisplayStatus(): ?string
    {
        return self::getStatusList()[$this->status];
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDING      => 'Invité',
            self::STATUS_REGISTERED   => 'Inscrit',
            self::STATUS_DECLINED     => 'Refus',
            self::STATUS_PARTICIPATED => 'Participant',
            self::STATUS_SWITCHED     => 'Permuté',
        ];
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        if (in_array($status, [self::STATUS_DECLINED, self::STATUS_PENDING, self::STATUS_SWITCHED])) {

            $this->workshops->clear();
        }
        
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDisplayType(): ?string
    {
        return self::getTypesList()[$this->type];
    }

    public function getTypesList(): array
    {
        return [
            self::TYPE_GUEST    => 'Invité',
            self::TYPE_PROSPECT => 'Prospect',
            self::TYPE_SIDEKICK => 'Accompagnant',
        ];
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addGuest(self $guest): self
    {
        if (!$this->children->contains($guest)) {
            $this->children[] = $guest;
            $guest->setParent($this);
        }

        return $this;
    }

    public function removeGuest(self $guest): self
    {
        if ($this->children->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getParent() === $this) {
                $guest->setParent(null);
            }
        }

        return $this;
    }

		public function getRoot(): ?Guest
		{
			$criteria = new Criteria();

			$criteria->andWhere(Criteria::expr()->eq('backup', false));

			$match = $this->children->matching($criteria);

			return $match->count() ? $match->first() : null;
		}

    /**
     * @return Collection|self[]
     */
    public function getProspects(): Collection
    {
        if (! $this->prospects) $this->prospects = new ArrayCollection();

        return $this->prospects;
    }

    public function addProspect(self $prospect): self
    {
        if (!$this->prospects instanceof ArrayCollection) {

            $this->prospects = new ArrayCollection();
        }

        if (!$this->prospects->contains($prospect)) {
            $this->prospects[] = $prospect;
            $prospect->setParent($this);
        }

        return $this;
    }

    public function removeProspect(self $prospect): self
    {
        if (!$this->prospects instanceof ArrayCollection) {

            $this->prospects = new ArrayCollection();
        }

        if ($this->prospects->removeElement($prospect)) {
            // set the owning side to null (unless already changed)
            if ($prospect->getParent() === $this) {
                $prospect->setParent(null);
            }
        }

        return $this;
    }

    public function isProspect(): ?bool
    {
        return $this->type === self::TYPE_PROSPECT;
    }

    public function setProspect(): self
    {
        $this->type = self::TYPE_PROSPECT;

        return $this;
    }

    public function setSidekick(): self
    {
        $this->type = self::TYPE_SIDEKICK;

        return $this;
    }

    public function getGolfLicense(): ?string
    {
        return $this->golfLicense;
    }

    public function setGolfLicense(?string $golfLicense): self
    {
        $this->golfLicense = $golfLicense;

        return $this;
    }


    public function getGolfIndex(): ?string
    {
        return $this->golfIndex;
    }

    public function setGolfIndex(?string $golfIndex): self
    {
        $this->golfIndex = $golfIndex;

        return $this;
    }

    public function getBackup(): ?bool
    {
        return $this->backup;
    }

    public function setBackup(bool $backup = true): self
    {
        $this->backup = $backup;

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRegistered(bool $includeParticipated = false): bool
    {
        if ($includeParticipated && $this->status === self::STATUS_PARTICIPATED) return true;

        return $this->status === self::STATUS_REGISTERED;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isParticipated(): bool
    {
        return $this->status === self::STATUS_PARTICIPATED;
    }

    public function isGuest(): bool
    {
        return $this->status === self::TYPE_GUEST;
    }

    public function isSidekick(): bool
    {
        return $this->type === self::TYPE_SIDEKICK;
    }

    public function isBackup(): bool
    {
        return $this->backup;
    }

    public function setRegistered(): self
    {
        $this->status = self::STATUS_REGISTERED;
        $this->sendEmail = self::STATUS_REGISTERED;

        return $this;
    }

    public function setDeclined(): self
    {
        $this->status = self::STATUS_DECLINED;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSidekicks(): Collection
    {
        if (!$this->sidekicks) $this->sidekicks = new ArrayCollection();

        return $this->sidekicks;
    }

    public function addSidekick(self $sidekick): self
    {
        if (!$this->getSidekicks()->contains($sidekick)) {
            $this->sidekicks[] = $sidekick;
        }

        return $this;
    }

    public function removeSidekick(self $sidekick): self
    {
        $this->sidekicks->removeElement($sidekick);

        return $this;
    }

    /**
     * @return Collection<int, WorkshopTimeslot>
     */
    public function getWorkshops(): Collection
    {
        return $this->workshops;
    }

    public function addWorkshop(WorkshopTimeslot $workshop): self
    {
        if (!$this->workshops->contains($workshop)) {
            $this->workshops[] = $workshop;
//            $workshop->addGuest($this);
        }

        return $this;
    }

    public function removeWorkshop(WorkshopTimeslot $workshop): self
    {
        $this->workshops->removeElement($workshop);

        return $this;
    }

    public function isSwitched(): bool
    {
        return $this->status === self::STATUS_SWITCHED;
    }

    public function setSwitched(): self
    {
        $this->status = self::STATUS_SWITCHED;

        return $this;
    }

    public function setParticipated(): self
    {
        $this->status = self::STATUS_PARTICIPATED;

        return $this;
    }

    public function getSendEmail(): ?string
    {
        return $this->sendEmail;
    }

    public function setSendEmail(?string $sendEmail): self
    {
        $this->sendEmail = $sendEmail;

        return $this;
    }

    public function getInspecteurCommercial(): ?string
    {
        return $this->inspecteurCommercial;
    }

    public function setInspecteurCommercial(?string $inspecteurCommercial): self
    {
        $this->inspecteurCommercial = $inspecteurCommercial;

        return $this;
    }

		public function getCurrentGuest(): ?Guest
		{
			$criteria = Criteria::create()
				->andWhere(Criteria::expr()->eq('backup', false));

			$res = $this->getChildren()->matching($criteria);

			return $res->count() ? $res->first() : null;
		}

    public function getParticipationType(): ?string
    {
        return $this->participationType;
    }

    public function setParticipationType(?string $participationType): self
    {
        $this->participationType = $participationType;
        return $this;
    }

    /**
     * @return Collection<int, EventMoment>
     */
    public function getMoments(): Collection
    {
        if (!$this->moments) {
            $this->moments = new ArrayCollection();
        }
        return $this->moments;
    }

    public function addMoment(EventMoment $moment): self
    {
        if (!$this->moments->contains($moment)) {
            $this->moments->add($moment);
            $moment->addGuest($this);
        }
        return $this;
    }

    public function removeMoment(EventMoment $moment): self
    {
        if ($this->moments->removeElement($moment)) {
        }
        return $this;
    }

    /**
     * @return Collection<int, GuestMomentChoice>
     */
    public function getMomentChoices(): Collection
    {
        return $this->momentChoices;
    }

    public function addMomentChoice(GuestMomentChoice $momentChoice): self
    {
        if (!$this->momentChoices->contains($momentChoice)) {
            $this->momentChoices->add($momentChoice);
            $momentChoice->setGuest($this);
        }
        return $this;
    }

    public function removeMomentChoice(GuestMomentChoice $momentChoice): self
    {
        if ($this->momentChoices->removeElement($momentChoice)) {
            if ($momentChoice->getGuest() === $this) {
                $momentChoice->setGuest(null);
            }
        }
        return $this;
    }
}
