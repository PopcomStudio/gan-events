<?php

namespace App\Entity;

use App\Repository\EventRepository;
use App\Repository\SenderRepository;
use App\Repository\ViewRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 * @UniqueEntity("slug")
 */
class Event
{
    public const TYPE_STANDARD   = 'evenement';
    public const TYPE_GOLFCUP    = 'golfcup';
    public const TYPE_CINEMA     = 'projection';
    public const TYPE_WORKSHOPS  = 'ateliers';
    public const TYPE_COLLECTION = 'collection';
    public const TYPE_STANDARD_PLUS_MOMENTS = 'standard_plus_moments';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Assert\Choice({"evenement", "golfcup", "projection", "ateliers", "collection", "standard_plus_moments"})
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $beginAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $foundation = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="events")
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=Sender::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $senders;

    private $currSender;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"all"})
     */
    private $visual;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"all"})
     */
    private $ticketVisual;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $target;

    /**
     * @ORM\OneToMany(targetEntity=EmailTemplate::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $emailTemplates;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $emailVisual;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $emailReminderVisual;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $emailUpVisual;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $emailThanksVisual;

    /**
     * @ORM\OneToMany(targetEntity=EmailSchedule::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $schedules;

    /**
     * @ORM\Column(type="string", length=191, unique=true, nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalTickets;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=8, nullable=true)
     */
    private $lat;

    /**
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $lng;

    /**
     * @ORM\OneToMany(targetEntity=Guest::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $guests;

    /**
     * @ORM\ManyToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $logo;

    /**
     * @ORM\OneToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $poster;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     * @ORM\JoinTable(name="event_manager")
     */
    private $managers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     * @ORM\JoinTable(name="event_viewer")
     */
    private $viewers;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $movieTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $movieGenres;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $movieDirectedBy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $MovieStarredBy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $movieCountries;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $movieRunningTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $movieReleasedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $movieAwards;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $movieOverview;

    /**
     * @ORM\OneToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $moviePoster;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero()
     */
    private $minWorkshop = 0;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero()
     */
    private $maxWorkshop = 0;

    /**
     * @ORM\OneToMany(targetEntity=Workshop::class, mappedBy="event", orphanRemoval=true, cascade={"all"})
     */
    private $workshops;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $archivedAt;

    /**
     * @ORM\OneToMany(targetEntity=View::class, mappedBy="event")
     */
    private $views;

    private ?User $currentUser;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finishAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $addTicket = false;

    /**
     * @ORM\OneToMany(targetEntity=EventMoment::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $moments;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $switchable;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $closedAt;

    /**
     * @ORM\OneToMany(targetEntity=TimeSlot::class, mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $timeSlots;

    /* ----------------------------------- */

    public function __construct()
    {
        $this->senders = new ArrayCollection();
        $this->emailTemplates = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->guests = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->viewers = new ArrayCollection();
        $this->workshops = new ArrayCollection();
        $this->timeSlots = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->moments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function clearMovie(): self
    {
        $this
            ->setMovieAwards(null)
            ->setMovieCountries(null)
            ->setMovieDirectedBy(null)
            ->setMovieGenres(null)
            ->setMovieOverview(null)
            ->setMovieReleasedAt(null)
            ->setMovieRunningTime(null)
            ->setMovieStarredBy(null)
            ->setMovieTitle(null)
            ->setMoviePoster(null)
        ;

        return $this;
    }

    public function clearWorkshops(): self
    {
        $this
            ->setMinWorkshop(0)
            ->setMaxWorkshop(0)
            ->workshops = new ArrayCollection()
        ;

        return $this;
    }

    public function allowedEmailPanel()
    {
        return
            $this->currSender &&
            (
                $this->currSender->getUser()->getId() === $this->owner->getId() ||
                ($this->currSender->getAutonomyOnEmails() || $this->currSender->getAutonomyOnSchedule())
            )
        ;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_STANDARD  => 'Standard',
            self::TYPE_GOLFCUP   => 'Golf Cup',
            self::TYPE_CINEMA    => 'Projection',
            self::TYPE_WORKSHOPS => 'Ateliers',
            self::TYPE_STANDARD_PLUS_MOMENTS => 'Standard avec temps forts',
        ];
    }

    public function getDisplayType(): ?string
    {
        return $this->type && isset(self::getTypes()[$this->type]) ? self::getTypes()[$this->type] : null;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getBeginAt(): ?\DateTimeInterface
    {
        return $this->beginAt;
    }

    public function setBeginAt(\DateTimeInterface $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getFoundation(): ?bool
    {
        return $this->foundation;
    }

    public function setFoundation(bool $foundation): self
    {
        $this->foundation = $foundation;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|Sender[]
     */
    public function getSenders(): Collection
    {
        return $this->senders;
    }

    public function addSender(Sender $sender): self
    {
        if (!$this->senders->contains($sender)) {
            $this->senders[] = $sender;
            $sender->setEvent($this);
        }

        return $this;
    }

    public function removeSender(Sender $sender): self
    {
        if ($this->senders->removeElement($sender)) {
            // set the owning side to null (unless already changed)
            if ($sender->getEvent() === $this) {
                $sender->setEvent(null);
            }
        }

        return $this;
    }

    public function getCurrSender(?User $user = null): ?Sender
    {
        if ($this->currSender) return $this->currSender;

        $criteria = new Criteria();
        $criteria->andWhere($criteria::expr()->eq('user', $user));

        $matches = $this->senders->matching($criteria);

        $this->currSender = $matches->count() ? $matches->first() : null;

        return $this->currSender;
    }

    public function setCurrSender(?Sender $sender): self
    {
        $this->currSender = $sender;

        return $this;
    }

    public function getVisual(): ?Attachment
    {
        return $this->visual;
    }

    public function setVisual(?Attachment $visual): self
    {
        $this->visual = $visual;

        return $this;
    }

    public function getTicketVisual(): ?Attachment
    {
        return $this->ticketVisual;
    }

    public function setTicketVisual(?Attachment $ticketVisual): self
    {
        $this->ticketVisual = $ticketVisual;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): self
    {
        $this->target = $target;

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
            $emailTemplate->setEvent($this);
        }

        return $this;
    }

    public function removeEmailTemplate(EmailTemplate $emailTemplate): self
    {
        if ($this->emailTemplates->removeElement($emailTemplate)) {
            // set the owning side to null (unless already changed)
            if ($emailTemplate->getEvent() === $this) {
                $emailTemplate->setEvent(null);
            }
        }

        return $this;
    }

    public function getEmailVisual($schedule = null): ?Attachment
    {
        if ($schedule instanceof EmailSchedule) {

            $visual = null;

            switch ($schedule->getType()):
                case 'invitation':
                    $visual = $this->getEmailVisual();
                    break;
                case 'up':
                    $visual = $this->getEmailUpVisual() ?: $this->getEmailVisual();
                    break;
                case 'reminder':
                    $visual = $this->getEmailReminderVisual() ?: $this->getEmailVisual();
                    break;
                case 'thanks':
                    $visual = $this->getEmailThanksVisual() ?: $this->getEmailVisual();
                    break;
            endswitch;

            if (!$visual) $visual = $this->getVisual();

            return $visual;

        } elseif ($schedule === 'confirmation') {

            $visual = $this->getEmailVisual();

            if (!$visual) $visual = $this->getVisual();

            return $visual;
        }

        return $this->emailVisual;
    }

    public function setEmailVisual(?Attachment $emailVisual): self
    {
        $this->emailVisual = $emailVisual;

        return $this;
    }

    public function getEmailReminderVisual(): ?Attachment
    {
        return $this->emailReminderVisual;
    }

    public function setEmailReminderVisual(?Attachment $emailReminderVisual): self
    {
        $this->emailReminderVisual = $emailReminderVisual;

        return $this;
    }

    public function getEmailUpVisual(): ?Attachment
    {
        return $this->emailUpVisual;
    }

    public function setEmailUpVisual(?Attachment $emailUpVisual): self
    {
        $this->emailUpVisual = $emailUpVisual;

        return $this;
    }

    public function getEmailThanksVisual(): ?Attachment
    {
        return $this->emailThanksVisual;
    }

    public function setEmailThanksVisual(?Attachment $emailThanksVisual): self
    {
        $this->emailThanksVisual = $emailThanksVisual;

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
            $schedule->setEvent($this);
        }

        return $this;
    }

    public function removeSchedule(EmailSchedule $schedule): self
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getEvent() === $this) {
                $schedule->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Guest[]
     */
    public function getGuests(): Collection
    {
        return $this->guests;
    }

    public function addGuest(Guest $guest): self
    {
        if (!$this->guests->contains($guest)) {
            $this->guests[] = $guest;
            $guest->setEvent($this);
        }

        return $this;
    }

    public function removeGuest(Guest $guest): self
    {
        if ($this->guests->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getEvent() === $this) {
                $guest->setEvent(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTotalTickets(bool $registered = false): ?int
    {
        if ($registered) {

            $criteria = new Criteria();

            $criteria->andWhere(Criteria::expr()->in('status', ['registered', 'participated']));
            $criteria->andWhere(Criteria::expr()->eq('backup', false));

            return $this->guests->matching($criteria)->count();
        }

        return $this->totalTickets;
    }

    public function setTotalTickets(?int $totalTickets): self
    {
        $this->totalTickets = $totalTickets;

        return $this;
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(?string $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?string
    {
        return $this->lng;
    }

    public function setLng(?string $lng): self
    {
        $this->lng = $lng;

        return $this;
    }

    public function getLogo(): ?Attachment
    {
        return $this->logo;
    }

    public function setLogo(?Attachment $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getPoster(): ?Attachment
    {
        return $this->poster;
    }

    public function setPoster(?Attachment $poster): self
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(User $manager): self
    {
        if (!$this->managers->contains($manager)) {
            $this->managers[] = $manager;
        }

        return $this;
    }

    public function removeManager(User $manager): self
    {
        $this->managers->removeElement($manager);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getViewers(): Collection
    {
        return $this->viewers;
    }

    public function addViewer(User $viewer): self
    {
        if (!$this->viewers->contains($viewer)) {
            $this->viewers[] = $viewer;
        }

        return $this;
    }

    public function removeViewer(User $viewer): self
    {
        $this->viewers->removeElement($viewer);

        return $this;
    }

    public function getMovieTitle(): ?string
    {
        return $this->movieTitle;
    }

    public function setMovieTitle(?string $movieTitle): self
    {
        $this->movieTitle = $movieTitle;

        return $this;
    }

    public function getMovieGenres(): ?string
    {
        return $this->movieGenres;
    }

    public function setMovieGenres(?string $movieGenres): self
    {
        $this->movieGenres = $movieGenres;

        return $this;
    }

    public function getMovieDirectedBy(): ?string
    {
        return $this->movieDirectedBy;
    }

    public function setMovieDirectedBy(?string $movieDirectedBy): self
    {
        $this->movieDirectedBy = $movieDirectedBy;

        return $this;
    }

    public function getMovieStarredBy(): ?string
    {
        return $this->MovieStarredBy;
    }

    public function setMovieStarredBy(?string $MovieStarredBy): self
    {
        $this->MovieStarredBy = $MovieStarredBy;

        return $this;
    }

    public function getMovieCountries(): ?string
    {
        return $this->movieCountries;
    }

    public function setMovieCountries(?string $movieCountries): self
    {
        $this->movieCountries = $movieCountries;

        return $this;
    }

    public function getMovieRunningTime(): ?int
    {
        return $this->movieRunningTime;
    }

    public function setMovieRunningTime(?int $movieRunningTime): self
    {
        $this->movieRunningTime = $movieRunningTime;

        return $this;
    }

    public function getMovieReleasedAt(): ?\DateTime
    {
        return $this->movieReleasedAt;
    }

    public function setMovieReleasedAt(?\DateTime $movieReleasedAt): self
    {
        $this->movieReleasedAt = $movieReleasedAt;

        return $this;
    }

    public function getMovieAwards(): ?string
    {
        return $this->movieAwards;
    }

    public function setMovieAwards(?string $movieAwards): self
    {
        $this->movieAwards = $movieAwards;

        return $this;
    }

    public function getMovieOverview(): ?string
    {
        return $this->movieOverview;
    }

    public function setMovieOverview(?string $movieOverview): self
    {
        $this->movieOverview = $movieOverview;

        return $this;
    }

    public function getMoviePoster(): ?Attachment
    {
        return $this->moviePoster;
    }

    public function setMoviePoster(?Attachment $moviePoster): self
    {
        $this->moviePoster = $moviePoster;

        return $this;
    }

    public function getMinWorkshop(): ?int
    {
        return $this->minWorkshop;
    }

    public function setMinWorkshop(?int $minWorkshop): self
    {
        $this->minWorkshop = $minWorkshop;

        return $this;
    }

    public function getMaxWorkshop(): ?int
    {
        return $this->maxWorkshop;
    }

    public function setMaxWorkshop(?int $maxWorkshop): self
    {
        $this->maxWorkshop = $maxWorkshop;

        return $this;
    }

    /**
     * @return Collection|Workshop[]
     */
    public function getWorkshops(): Collection
    {
        return $this->workshops;
    }

    public function addWorkshop(Workshop $workshop): self
    {
        if (!$this->workshops->contains($workshop)) {
            $this->workshops[] = $workshop;
            $workshop->setEvent($this);
        }

        return $this;
    }

    public function removeWorkshop(Workshop $workshop): self
    {
        if ($this->workshops->removeElement($workshop)) {
            // set the owning side to null (unless already changed)
            if ($workshop->getEvent() === $this) {
                $workshop->setEvent(null);
            }
        }

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeInterface
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeInterface $archivedAt): self
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }

    public function toggleArchived(): self
    {
        $this->archivedAt = $this->archivedAt === null ? new DateTime() : null;

        return $this;
    }

    /**
     * @return Collection|View[]
     */
    public function getViews(): Collection
    {
        return $this->views;
    }

    public function getView(User $user, bool $manager = false): ?View
    {
        // Récupérer la vue de l'utilisateur
        $matches = $this->views->matching(ViewRepository::createUserCriteria($user));

        // Retourne la vue si elle existe
        if ( $matches->count() ) return $matches->first();

        /** Si la vue n'existe pas, créer une vue */

        if ( $manager ) {

            $sender = null;

        } else {

            $matches = $this->senders->matching(SenderRepository::createUserCriteria($user));
            $sender = $matches->count() ? $matches->first(): null;
        }

        return new View($user, $this, $sender);
    }

    public function setCurrentUser(?User $user): self
    {
        $this->currentUser = $user;

        return $this;
    }

    public function isOwner(): bool
    {
        return $this->owner === $this->currentUser;
    }

    public function isManager(): bool
    {
        return $this->managers->contains($this->managers);
    }

    public function isViewer(): bool
    {
        return $this->viewers->contains($this->currentUser);
    }

    public function getFinishAt(): ?\DateTimeInterface
    {
        return $this->finishAt;
    }

    public function setFinishAt(?\DateTimeInterface $finishAt): self
    {
        $this->finishAt = $finishAt;

        return $this;
    }

    public function getAddTicket(): ?bool
    {
        return $this->addTicket;
    }

    public function setAddTicket(bool $addTicket): self
    {
        $this->addTicket = $addTicket;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    public function getTimeSlots(): Collection
    {
        return $this->timeSlots;
    }

    public function addTimeSlot(TimeSlot $timeSlot): self
    {
        if (!$this->timeSlots->contains($timeSlot)) {
            $this->timeSlots[] = $timeSlot;
            $timeSlot->setEvent($this);
        }

        return $this;
    }

    public function removeTimeSlot(TimeSlot $timeSlot): self
    {
        if ($this->timeSlots->removeElement($timeSlot)) {
            // set the owning side to null (unless already changed)
            if ($timeSlot->getEvent() === $this) {
                $timeSlot->setEvent(null);
            }
        }

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
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getFirstChild(?Event $without = null): ?Event
    {
        $criteria = new Criteria();
        $criteria->orderBy(['beginAt' => 'DESC'])->setMaxResults(1);

        if ($without) $criteria->andWhere(Criteria::expr()->neq('id', $without->getId()));

        return $this->children->matching($criteria)->first() ?: null;
    }

    public function getOtherEvents(Event $event): Collection
    {
        $criteria = new Criteria();
        $criteria->orderBy(['beginAt' => 'DESC']);

        $criteria->andWhere(Criteria::expr()->eq('switchable', true));
        $criteria->andWhere(Criteria::expr()->neq('id', $event));
        $criteria->andWhere(Criteria::expr()->gt('beginAt', (new DateTime()))); // Todo: Vérifier également la date de fin

        return $this->children->matching($criteria);
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function isSwitchable(): ?bool
    {
        return $this->switchable;
    }

    public function setSwitchable(?bool $switchable): self
    {
        $this->switchable = $switchable;

        return $this;
    }

    public function isCollection(): bool
    {
        return $this->type === self::TYPE_COLLECTION;
    }

    public function isInCollection(): bool
    {
        return $this->parent !== null;
    }

    /**
     * @return Collection|EventMoment[]
     */
    public function getMoments(): Collection
    {
        return $this->moments;
    }

    public function addMoment(EventMoment $moment): self
    {
        if (!$this->moments->contains($moment)) {
            $this->moments[] = $moment;
            $moment->setEvent($this);
        }

        return $this;
    }

    public function removeMoment(EventMoment $moment): self
    {
        if ($this->moments->removeElement($moment)) {
            // set the owning side to null (unless already changed)
            if ($moment->getEvent() === $this) {
                $moment->setEvent(null);
            }
        }

        return $this;
    }
}
