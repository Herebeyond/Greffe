<?php

namespace App\Entity;

use App\Repository\BreakTheGlassAccessRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Records a "break-the-glass" (bris de glace) emergency access to a patient file.
 *
 * When a healthcare professional needs to access a patient they are not
 * normally authorized to view, they must provide a justification. The access
 * is time-limited and logged for mandatory a posteriori audit.
 *
 * Legal basis: CNIL Référentiel Hôpital (2021), Art. L1110-4 CSP, RGPD Art. 9(2)(c).
 */
#[ORM\Entity(repositoryClass: BreakTheGlassAccessRepository::class)]
#[ORM\Table(name: 'break_the_glass_access')]
#[ORM\Index(columns: ['user_id', 'patient_id'], name: 'idx_btg_user_patient')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_btg_expires')]
#[ORM\Index(columns: ['accessed_at'], name: 'idx_btg_accessed')]
class BreakTheGlassAccess
{
    /** Default duration of emergency access in minutes. */
    public const DEFAULT_DURATION_MINUTES = 180;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Patient $patient;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La justification est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'La justification doit contenir au moins {{ limit }} caractères')]
    private string $justification;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $accessedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $reviewed = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct()
    {
        $this->accessedAt = new \DateTimeImmutable();
        $this->expiresAt = $this->accessedAt->modify('+' . self::DEFAULT_DURATION_MINUTES . ' minutes');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function setPatient(Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getJustification(): string
    {
        return $this->justification;
    }

    public function setJustification(string $justification): static
    {
        $this->justification = $justification;

        return $this;
    }

    public function getAccessedAt(): \DateTimeImmutable
    {
        return $this->accessedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function isReviewed(): bool
    {
        return $this->reviewed;
    }

    public function setReviewed(bool $reviewed): static
    {
        $this->reviewed = $reviewed;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }
}
