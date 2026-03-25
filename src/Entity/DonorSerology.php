<?php

namespace App\Entity;

use App\Entity\Reference\SerologyMarker;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'donor_serology')]
#[ORM\UniqueConstraint(name: 'uniq_donor_serology_marker', columns: ['donor_id', 'serology_marker_id'])]
class DonorSerology
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Donor::class, inversedBy: 'serologyResults')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Donor $donor = null;

    #[ORM\ManyToOne(targetEntity: SerologyMarker::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SerologyMarker $serologyMarker = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank(message: 'Le résultat sérologique est obligatoire')]
    private ?string $result = null;

    public function getId(): ?int { return $this->id; }

    public function getDonor(): ?Donor { return $this->donor; }
    public function setDonor(?Donor $donor): static { $this->donor = $donor; return $this; }

    public function getSerologyMarker(): ?SerologyMarker { return $this->serologyMarker; }
    public function setSerologyMarker(?SerologyMarker $serologyMarker): static { $this->serologyMarker = $serologyMarker; return $this; }

    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }
}
