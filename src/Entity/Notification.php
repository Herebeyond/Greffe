<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER') and object.getRecipient() == user"),
        new Patch(
            security: "is_granted('ROLE_USER') and object.getRecipient() == user",
            denormalizationContext: ['groups' => ['notification:write']],
        ),
    ],
    normalizationContext: ['groups' => ['notification:read']],
    paginationItemsPerPage: 30,
    order: ['createdAt' => 'DESC'],
)]
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['recipient_id', 'is_read'], name: 'idx_notification_recipient_read')]
#[ORM\Index(columns: ['created_at'], name: 'idx_notification_created')]
class Notification
{
    // Notification types
    public const TYPE_ACCESS_GRANTED = 'access_granted';
    public const TYPE_ACCESS_REVOKED = 'access_revoked';
    public const TYPE_ACCESS_TRANSFERRED = 'access_transferred';
    public const TYPE_DONOR_LINKED = 'donor_linked';
    public const TYPE_PATIENT_EDITED = 'patient_edited';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['notification:read'])]
    private ?User $recipient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['notification:read'])]
    private ?User $triggeredBy = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:read'])]
    private string $type;

    #[ORM\Column(length: 500)]
    #[Groups(['notification:read'])]
    private string $message;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['notification:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['notification:read'])]
    private ?Patient $relatedPatient = null;

    #[ORM\ManyToOne(targetEntity: Donor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['notification:read'])]
    private ?Donor $relatedDonor = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getTriggeredBy(): ?User
    {
        return $this->triggeredBy;
    }

    public function setTriggeredBy(?User $triggeredBy): static
    {
        $this->triggeredBy = $triggeredBy;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function markAsRead(): static
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();

        return $this;
    }

    public function getRelatedPatient(): ?Patient
    {
        return $this->relatedPatient;
    }

    public function setRelatedPatient(?Patient $relatedPatient): static
    {
        $this->relatedPatient = $relatedPatient;

        return $this;
    }

    public function getRelatedDonor(): ?Donor
    {
        return $this->relatedDonor;
    }

    public function setRelatedDonor(?Donor $relatedDonor): static
    {
        $this->relatedDonor = $relatedDonor;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ACCESS_GRANTED => 'Accès accordé',
            self::TYPE_ACCESS_REVOKED => 'Accès révoqué',
            self::TYPE_ACCESS_TRANSFERRED => 'Accès transféré',
            self::TYPE_DONOR_LINKED => 'Donneur associé',
            self::TYPE_PATIENT_EDITED => 'Patient modifié',
            default => 'Notification',
        };
    }
}
