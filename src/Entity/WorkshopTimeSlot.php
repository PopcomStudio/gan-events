<?php

namespace App\Entity;

use App\Repository\WorkshopTimeSlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WorkshopTimeSlotRepository::class)
 */
class WorkshopTimeSlot
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Workshop::class, inversedBy="timeSlots")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $workshop;

    /**
     * @ORM\ManyToOne(targetEntity=TimeSlot::class, inversedBy="workshops")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $timeSlot;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbGuests;

    /**
     * @ORM\ManyToMany(targetEntity=Guest::class, mappedBy="workshops")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $guests;

    public function __construct()
    {
//        $this->id = $id;
        $this->guests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkshop(): ?Workshop
    {
        return $this->workshop;
    }

    public function setWorkshop(?Workshop $workshop): self
    {
        $this->workshop = $workshop;

        return $this;
    }

    public function getTimeSlot(): ?TimeSlot
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(?TimeSlot $timeSlot): self
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    public function getNbGuests(): ?int
    {
        return $this->nbGuests;
    }

    public function setNbGuests(?int $nbGuests): self
    {
        $this->nbGuests = $nbGuests;

        return $this;
    }

    /**
     * @return Collection<int, Guest>
     */
    public function getGuests(bool $guestOnly = false): Collection
    {
        if ($guestOnly) {

            $criteria = Criteria::create();
            $criteria->andWhere(Criteria::expr()->eq('backup', 0));

            return $this->guests->matching($criteria);
        }

        return $this->guests;
    }

    public function addGuest(Guest $guest): self
    {
        if (!$this->guests->contains($guest)) {
            $this->guests[] = $guest;
            $guest->addWorkshop($this);
        }

        return $this;
    }

    public function removeGuest(Guest $guest): self
    {
        if ($this->guests->removeElement($guest)) {
            $guest->removeWorkshop($this);
        }

        return $this;
    }
}
