<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @UniqueEntity("email", message="Cette adresse est déjà utilisée.")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const NAMESPACE_REGISTER = '4dee6eb5-099f-4767-b9bd-cdf4590cbbf8';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    private ?string $plainPassword = null;

//    /**
//     * @link https://symfony.com/doc/current/reference/constraints/UserPassword.html
//     * @link https://symfony.com/doc/current/reference/constraints/NotCompromisedPassword.html
//     * @SecurityAssert\UserPassword(message = "Le mot de passe est incorrect.")
//     */
//    protected $oldPassword;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="owner")
     */
    private $events;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $registeredAt;

    /**
     * @ORM\OneToMany(targetEntity=Sender::class, mappedBy="user")
     */
    private $senders;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $agreedTermsAt;

    /**
     * @ORM\Column(type="string", length=17, nullable=true)
     */
    private $phone;

    private ?Event $eventRegistration;

    /* ------------------------------------------- */

    public function __construct(?Event $eventRegistration = null)
    {
        $this->registeredAt = new \DateTime();
        $this->events = new ArrayCollection();
        $this->senders = new ArrayCollection();

        $this->eventRegistration = $eventRegistration;
    }

    public function getRegisterKey(): string
    {
        return Uuid::uuid5(self::NAMESPACE_REGISTER, $this->email);
    }

    public static function verifyRegisterKey($key, $email): bool
    {
        return $key === Uuid::uuid5(self::NAMESPACE_REGISTER, $email)->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     */
    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
        $this->password = $plainPassword;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
         $this->plainPassword = null;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setOwner($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getOwner() === $this) {
                $event->setOwner(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): ?string
    {
        $displayName = '';

        if ($this->firstName) $displayName.= $this->firstName;
        if ($this->lastName && $displayName) $displayName.= ' ';
        if ($this->lastName) $displayName.= $this->lastName;
        if (!$displayName) $displayName = $this->email;

        return $displayName;
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

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
            $sender->setUser($this);
        }

        return $this;
    }

    public function removeSender(Sender $sender): self
    {
        if ($this->senders->removeElement($sender)) {
            // set the owning side to null (unless already changed)
            if ($sender->getUser() === $this) {
                $sender->setUser(null);
            }
        }

        return $this;
    }

    public function getAgreedTermsAt(): ?\DateTimeInterface
    {
        return $this->agreedTermsAt;
    }

    public function setAgreedTermsAt(?\DateTimeInterface $agreedTermsAt): self
    {
        $this->agreedTermsAt = $agreedTermsAt;

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

    public static function generatePassword()
    {
        $characters = [
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '@!-_&'
        ];
        $charTypes = count($characters);

        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $type = rand(0, $charTypes-1);
            $password .= $characters[$type][rand(0, strlen($characters[$type]) - 1)];
        }

        return $password;
    }

    public function getEventRegistration(): ?Event
    {
        return $this->eventRegistration;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function setAdmin(bool $admin): self
    {
        $res = array_search('ROLE_ADMIN', $this->roles);

        if ($admin) {

            if ($res === false) $this->roles[] = 'ROLE_ADMIN';

        } else {

            if ($res !== false) unset($this->roles[$res]);
        }

        return $this;
    }
}
