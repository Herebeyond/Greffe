<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Patient entity - represents a kidney transplant recipient.
 */
#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patient')]
#[ORM\Index(columns: ['file_number'], name: 'idx_patient_file_number')]
#[ORM\Index(columns: ['city'], name: 'idx_patient_city')]
#[UniqueEntity(fields: ['fileNumber'], message: 'Ce numéro de dossier est déjà utilisé')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Patient file number (numéro de dossier) - unique identifier within the hospital.
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le numéro de dossier est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le numéro de dossier ne peut pas dépasser {{ limit }} caractères')]
    private ?string $fileNumber = null;

    /**
     * Patient last name (nom) - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string')]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    /**
     * Patient first name (prénom) - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string')]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $firstName = null;

    /**
     * City of residence (ville de résidence).
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La ville de résidence est obligatoire')]
    #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères')]
    private ?string $city = null;

    /**
     * Date of birth - encrypted sensitive PII.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    /**
     * Blood group (A, B, AB, O).
     */
    #[ORM\Column(length: 3, nullable: true)]
    #[Assert\Choice(choices: ['A', 'B', 'AB', 'O'], message: 'Groupe sanguin invalide')]
    private ?string $bloodGroup = null;

    /**
     * Rhesus factor (+, -).
     */
    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['+', '-'], message: 'Rhésus invalide')]
    private ?string $rhesus = null;

    /**
     * Sex (M, F).
     */
    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['M', 'F'], message: 'Sexe invalide')]
    private ?string $sex = null;

    /**
     * Phone number - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    private ?string $phone = null;

    /**
     * Email address - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    #[Assert\Email(message: 'Adresse email invalide')]
    private ?string $email = null;

    /**
     * General comments about the patient.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    /**
     * When the patient record was created.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * When the patient record was last updated.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * External practitioners authorized to access this patient's file.
     * CHU practitioners don't need to be in this list (they have access via isChuPractitioner flag).
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\User::class, inversedBy: 'assignedPatients')]
    #[ORM\JoinTable(name: 'patient_authorized_user')]
    private Collection $authorizedPractitioners;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->authorizedPractitioners = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileNumber(): ?string
    {
        return $this->fileNumber;
    }

    public function setFileNumber(string $fileNumber): static
    {
        $this->fileNumber = $fileNumber;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBloodGroup(): ?string
    {
        return $this->bloodGroup;
    }

    public function setBloodGroup(?string $bloodGroup): static
    {
        $this->bloodGroup = $bloodGroup;

        return $this;
    }

    public function getRhesus(): ?string
    {
        return $this->rhesus;
    }

    public function setRhesus(?string $rhesus): static
    {
        $this->rhesus = $rhesus;

        return $this;
    }

    /**
     * Full blood group with rhesus (e.g. "A+", "O-").
     */
    public function getFullBloodGroup(): ?string
    {
        if ($this->bloodGroup === null) {
            return null;
        }

        return $this->bloodGroup . ($this->rhesus ?? '');
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): static
    {
        $this->sex = $sex;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get the full name of the patient.
     */
    public function getFullName(): string
    {
        return $this->lastName . ' ' . $this->firstName;
    }

    /**
     * Calculate the patient's age based on birth date.
     */
    public function getAge(): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }

        $now = new \DateTime();
        $diff = $now->diff($this->birthDate);

        return $diff->y;
    }

    /**
     * Get sex label in French.
     */
    public function getSexLabel(): ?string
    {
        return match ($this->sex) {
            'M' => 'Homme',
            'F' => 'Femme',
            default => null,
        };
    }

    /**
     * @return Collection<int, \App\Entity\User>
     */
    public function getAuthorizedPractitioners(): Collection
    {
        return $this->authorizedPractitioners;
    }

    public function addAuthorizedPractitioner(\App\Entity\User $user): static
    {
        if (!$this->authorizedPractitioners->contains($user)) {
            $this->authorizedPractitioners->add($user);
        }

        return $this;
    }

    public function removeAuthorizedPractitioner(\App\Entity\User $user): static
    {
        $this->authorizedPractitioners->removeElement($user);

        return $this;
    }

    public function isAuthorizedPractitioner(\App\Entity\User $user): bool
    {
        return $this->authorizedPractitioners->contains($user);
    }
}
