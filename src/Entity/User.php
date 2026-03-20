<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_CRISTAL_ID', fields: ['cristalId'])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée')]
#[UniqueEntity(fields: ['cristalId'], message: 'Cet identifiant CRISTAL est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var bool Whether the user account is active
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var \DateTimeImmutable|null The last successful login time
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * @var \DateTimeImmutable|null When the account was created
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var string|null Encrypted - Name is sensitive PII
     */
    #[ORM\Column(type: 'encrypted_string')]
    private ?string $name = null;

    /**
     * @var string|null Encrypted - Surname is sensitive PII
     */
    #[ORM\Column(type: 'encrypted_string')]
    private ?string $surname = null;

    /**
     * @var string|null Encrypted - CRISTAL identifier is sensitive medical data
     */
    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    private ?string $cristalId = null;

    /**
     * @var \DateTimeImmutable|null When the password was last changed
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;



    /**
     * @deprecated No longer used for access control. Kept for DB compatibility.
     * All practitioners now access only their assigned patients.
     * Break-the-glass provides audited emergency access to unassigned patients.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isChuPractitioner = false;

    /**
     * Patients assigned to this practitioner.
     * All practitioners (CHU or external) can only access their assigned patients.
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Patient::class, mappedBy: 'authorizedPractitioners')]
    private Collection $assignedPatients;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->assignedPatients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
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
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getCristalId(): ?string
    {
        return $this->cristalId;
    }

    public function setCristalId(?string $cristalId): static
    {
        $this->cristalId = $cristalId;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isChuPractitioner(): bool
    {
        return $this->isChuPractitioner;
    }

    public function setIsChuPractitioner(bool $isChuPractitioner): static
    {
        $this->isChuPractitioner = $isChuPractitioner;

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Patient>
     */
    public function getAssignedPatients(): Collection
    {
        return $this->assignedPatients;
    }

    /**
     * Get the full name of the user.
     */
    public function getFullName(): string
    {
        return $this->name . ' ' . $this->surname;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeImmutable $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;

        return $this;
    }

}
