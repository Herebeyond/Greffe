<?php

namespace App\Entity;

use App\Repository\PatientAccessRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tracks which practitioners have access to which patients, and at what level.
 *
 * Access levels:
 *  - "primary": Can manage (grant/revoke) access for this patient, transfer primary to another doctor.
 *  - "secondary": View/edit only (based on role), cannot manage access.
 *
 * Rules:
 *  - Only doctors can hold primary access.
 *  - Nurses always receive secondary access.
 *  - A patient should have one primary holder.
 *  - Primary holder or SUPER_ADMIN can grant/revoke/transfer access.
 */
#[ORM\Entity(repositoryClass: PatientAccessRepository::class)]
#[ORM\Table(name: 'patient_access')]
#[ORM\UniqueConstraint(name: 'unique_patient_user', columns: ['patient_id', 'user_id'])]
class PatientAccess
{
    public const LEVEL_PRIMARY = 'primary';
    public const LEVEL_SECONDARY = 'secondary';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'patientAccesses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'patientAccesses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $accessLevel = self::LEVEL_SECONDARY;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $grantedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $grantedAt;

    public function __construct()
    {
        $this->grantedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAccessLevel(): string
    {
        return $this->accessLevel;
    }

    public function setAccessLevel(string $accessLevel): static
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->accessLevel === self::LEVEL_PRIMARY;
    }

    public function getGrantedBy(): ?User
    {
        return $this->grantedBy;
    }

    public function setGrantedBy(?User $grantedBy): static
    {
        $this->grantedBy = $grantedBy;

        return $this;
    }

    public function getGrantedAt(): \DateTimeImmutable
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(\DateTimeImmutable $grantedAt): static
    {
        $this->grantedAt = $grantedAt;

        return $this;
    }
}
