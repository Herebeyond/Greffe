<?php

namespace App\Entity;

use App\Entity\Reference\VirologicalMarker;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'transplant_virological_status')]
#[ORM\UniqueConstraint(name: 'uniq_transplant_virological_marker', columns: ['transplant_id', 'virological_marker_id'])]
class TransplantVirologicalStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transplant::class, inversedBy: 'virologicalStatuses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Transplant $transplant = null;

    #[ORM\ManyToOne(targetEntity: VirologicalMarker::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?VirologicalMarker $virologicalMarker = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le statut virologique est obligatoire')]
    private ?string $status = null;

    public function getId(): ?int { return $this->id; }

    public function getTransplant(): ?Transplant { return $this->transplant; }
    public function setTransplant(?Transplant $transplant): static { $this->transplant = $transplant; return $this; }

    public function getVirologicalMarker(): ?VirologicalMarker { return $this->virologicalMarker; }
    public function setVirologicalMarker(?VirologicalMarker $virologicalMarker): static { $this->virologicalMarker = $virologicalMarker; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }
}
