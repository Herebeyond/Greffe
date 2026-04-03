<?php

namespace App\Entity\Reference;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\Reference\ConsultationTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['consultation_type:read']],
    paginationEnabled: false,
    order: ['displayOrder' => 'ASC'],
)]
#[ORM\Entity(repositoryClass: ConsultationTypeRepository::class)]
#[ORM\Table(name: 'ref_consultation_type')]
class ConsultationType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['consultation:read', 'consultation_type:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['consultation:read', 'consultation_type:read'])]
    private string $code;

    #[ORM\Column(length: 100)]
    #[Groups(['consultation:read', 'consultation_type:read'])]
    private string $label;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $displayOrder = 0;

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder): static { $this->displayOrder = $displayOrder; return $this; }
    public function __toString(): string { return $this->label; }
}
