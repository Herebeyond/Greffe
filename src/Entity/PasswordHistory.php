<?php

namespace App\Entity;

use App\Repository\PasswordHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordHistoryRepository::class)]
#[ORM\Table(name: 'password_history')]
#[ORM\Index(columns: ['user_id', 'changed_at'], name: 'idx_password_history_user_date')]
class PasswordHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * The hashed password that was replaced.
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $hashedPassword = null;

    /**
     * When this password was set (i.e. when the *new* password replaced it).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $changedAt;

    /**
     * Who triggered the password change.
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $changeReason;

    public const REASON_USER_RESET = 'user_reset';
    public const REASON_ADMIN_CHANGE = 'admin_change';
    public const REASON_SELF_CHANGE = 'self_change';

    public function __construct()
    {
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHashedPassword(): ?string
    {
        return $this->hashedPassword;
    }

    public function setHashedPassword(string $hashedPassword): static
    {
        $this->hashedPassword = $hashedPassword;

        return $this;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeImmutable $changedAt): static
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getChangeReason(): string
    {
        return $this->changeReason;
    }

    public function setChangeReason(string $changeReason): static
    {
        $this->changeReason = $changeReason;

        return $this;
    }

    public function getChangeReasonLabel(): string
    {
        return match ($this->changeReason) {
            self::REASON_USER_RESET => 'Réinitialisation par l\'utilisateur',
            self::REASON_ADMIN_CHANGE => 'Modification par un administrateur',
            self::REASON_SELF_CHANGE => 'Modification par l\'utilisateur',
            default => $this->changeReason,
        };
    }
}
