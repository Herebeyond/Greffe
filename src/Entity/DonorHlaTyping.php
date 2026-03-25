<?php

namespace App\Entity;

use App\Entity\Reference\HlaLocus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'donor_hla_typing')]
#[ORM\UniqueConstraint(name: 'uniq_donor_hla_locus', columns: ['donor_id', 'hla_locus_id'])]
class DonorHlaTyping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Donor::class, inversedBy: 'hlaTypings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Donor $donor = null;

    #[ORM\ManyToOne(targetEntity: HlaLocus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?HlaLocus $hlaLocus = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank(message: 'La valeur HLA est obligatoire')]
    private ?int $value = null;

    public function getId(): ?int { return $this->id; }

    public function getDonor(): ?Donor { return $this->donor; }
    public function setDonor(?Donor $donor): static { $this->donor = $donor; return $this; }

    public function getHlaLocus(): ?HlaLocus { return $this->hlaLocus; }
    public function setHlaLocus(?HlaLocus $hlaLocus): static { $this->hlaLocus = $hlaLocus; return $this; }

    public function getValue(): ?int { return $this->value; }
    public function setValue(?int $value): static { $this->value = $value; return $this; }
}
