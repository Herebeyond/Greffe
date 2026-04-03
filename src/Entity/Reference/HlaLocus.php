<?php

namespace App\Entity\Reference;

use App\Repository\Reference\HlaLocusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HlaLocusRepository::class)]
#[ORM\Table(name: 'ref_hla_locus')]
class HlaLocus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['donor:read', 'transplant:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Groups(['donor:read', 'transplant:read'])]
    private string $code;

    #[ORM\Column(length: 50)]
    #[Groups(['donor:read', 'transplant:read'])]
    private string $label;

    #[ORM\Column]
    private bool $isRequired = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $displayOrder = 0;

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function isRequired(): bool { return $this->isRequired; }
    public function setIsRequired(bool $isRequired): static { $this->isRequired = $isRequired; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder): static { $this->displayOrder = $displayOrder; return $this; }
    public function __toString(): string { return $this->label; }
}
