<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Reference\BloodGroup;
use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Patient entity - represents a kidney transplant recipient.
 */
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
        new Get(security: "is_granted('VIEW_PATIENT', object)"),
    ],
    normalizationContext: ['groups' => ['patient:read']],
    paginationItemsPerPage: 20,
)]
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
    #[Groups(['patient:read'])]
    private ?int $id = null;

    /**
     * Patient file number (numéro de dossier) - unique identifier within the hospital.
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le numéro de dossier est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le numéro de dossier ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['patient:read'])]
    private ?string $fileNumber = null;

    /**
     * Patient last name (nom) - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string')]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Groups(['patient:read'])]
    private ?string $lastName = null;

    /**
     * Patient first name (prénom) - encrypted sensitive PII.
     */
    #[ORM\Column(type: 'encrypted_string')]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Groups(['patient:read'])]
    private ?string $firstName = null;

    /**
     * City of residence (ville de résidence).
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La ville de résidence est obligatoire')]
    #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['patient:read'])]
    private ?string $city = null;

    /**
     * Date of birth - encrypted sensitive PII.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['patient:read'])]
    private ?\DateTimeInterface $birthDate = null;

    /**
     * Blood group reference.
     */
    #[ORM\ManyToOne(targetEntity: BloodGroup::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['patient:read'])]
    private ?BloodGroup $bloodGroup = null;

    /**
     * Rhesus factor (+, -).
     */
    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['+', '-'], message: 'Rhésus invalide')]
    #[Groups(['patient:read'])]
    private ?string $rhesus = null;

    /**
     * Sex (M, F).
     */
    #[ORM\Column(length: 1, nullable: true)]
    #[Assert\Choice(choices: ['M', 'F'], message: 'Sexe invalide')]
    #[Groups(['patient:read'])]
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
     * Access records for practitioners authorized to access this patient's file.
     * Each record tracks the access level (primary/secondary) and who granted it.
     *
     * @var Collection<int, PatientAccess>
     */
    #[ORM\OneToMany(targetEntity: PatientAccess::class, mappedBy: 'patient', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $patientAccesses;

    /**
     * @var Collection<int, Transplant>
     */
    #[ORM\OneToMany(targetEntity: Transplant::class, mappedBy: 'patient')]
    #[ORM\OrderBy(['transplantDate' => 'ASC'])]
    private Collection $transplants;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->patientAccesses = new ArrayCollection();
        $this->transplants = new ArrayCollection();
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

    public function getBloodGroup(): ?BloodGroup
    {
        return $this->bloodGroup;
    }

    public function setBloodGroup(?BloodGroup $bloodGroup): static
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

        return $this->bloodGroup->getCode() . ($this->rhesus ?? '');
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
     * @return Collection<int, PatientAccess>
     */
    public function getPatientAccesses(): Collection
    {
        return $this->patientAccesses;
    }

    public function addPatientAccess(PatientAccess $access): static
    {
        if (!$this->patientAccesses->contains($access)) {
            $this->patientAccesses->add($access);
            $access->setPatient($this);
        }

        return $this;
    }

    public function removePatientAccess(PatientAccess $access): static
    {
        if ($this->patientAccesses->removeElement($access)) {
            if ($access->getPatient() === $this) {
                $access->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * Check if a user has any access (primary or secondary) to this patient.
     */
    public function isAuthorizedPractitioner(User $user): bool
    {
        foreach ($this->patientAccesses as $access) {
            if ($access->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the access record for a specific user.
     */
    public function getAccessFor(User $user): ?PatientAccess
    {
        foreach ($this->patientAccesses as $access) {
            if ($access->getUser() === $user) {
                return $access;
            }
        }

        return null;
    }

    /**
     * Find the primary access holder.
     */
    public function getPrimaryAccess(): ?PatientAccess
    {
        foreach ($this->patientAccesses as $access) {
            if ($access->isPrimary()) {
                return $access;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Transplant>
     */
    public function getTransplants(): Collection
    {
        return $this->transplants;
    }

    /**
     * Get the number of transplants for this patient.
     */
    public function getTransplantCount(): int
    {
        return $this->transplants->count();
    }

    /**
     * Get the number of failed (non-functional) transplants.
     */
    public function getFailedTransplantCount(): int
    {
        $count = 0;
        foreach ($this->transplants as $transplant) {
            if (
                !$transplant->isGraftFunctional()
                && ($transplant->getGraftEndDate() !== null || $transplant->getGraftEndCause() !== null)
            ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get the number of assigned draft transplants awaiting medical completion.
     */
    public function getAssignedDraftTransplantCount(): int
    {
        $count = 0;
        foreach ($this->transplants as $transplant) {
            if (
                !$transplant->isGraftFunctional()
                && $transplant->getDonor() !== null
                && $transplant->getGraftEndDate() === null
                && $transplant->getGraftEndCause() === null
            ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check if the patient has at least one functional graft.
     */
    public function hasActiveFunctionalGraft(): bool
    {
        foreach ($this->transplants as $transplant) {
            if ($transplant->isGraftFunctional()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the patient needs a transplant (no transplants or all grafts failed).
     */
    public function needsTransplant(): bool
    {
        if ($this->transplants->isEmpty()) {
            return true;
        }
        return !$this->hasActiveFunctionalGraft();
    }

    /**
     * Get the transplant status label for display.
     * Returns: "En attente de greffe", "Greffé(e)", "Greffe échouée — en attente", etc.
     */
    public function getTransplantStatusLabel(): string
    {
        $total = $this->getTransplantCount();
        $failed = $this->getFailedTransplantCount();
        $assigned = $this->getAssignedDraftTransplantCount();

        if ($total === 0) {
            return 'En attente de greffe';
        }

        if ($this->hasActiveFunctionalGraft()) {
            if ($failed > 0) {
                return 'Greffé(e) (' . $failed . ' échec' . ($failed > 1 ? 's' : '') . ' ant.)';
            }
            return 'Greffé(e)';
        }

        if ($assigned > 0) {
            if ($failed > 0) {
                return 'En attente de re-greffe ('
                    . $failed . ' échec' . ($failed > 1 ? 's' : '')
                    . ', ' . $assigned . ' assigné' . ($assigned > 1 ? 's' : '') . ')';
            }

            return 'Greffe assignée (' . $assigned . ' en cours)';
        }

        // All grafts failed
        return 'En attente de re-greffe (' . $failed . ' échec' . ($failed > 1 ? 's' : '') . ')';
    }

    /**
     * Get the CSS class for the transplant status badge.
     */
    public function getTransplantStatusClass(): string
    {
        if ($this->transplants->isEmpty()) {
            return 'status-waiting';
        }

        if ($this->hasActiveFunctionalGraft()) {
            return 'status-active';
        }

        if ($this->getAssignedDraftTransplantCount() > 0) {
            return 'status-waiting';
        }

        return 'status-failed';
    }
}
