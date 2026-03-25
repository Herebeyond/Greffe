<?php

namespace App\Entity;

use App\Entity\Reference\HlaLocus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'transplant_hla_incompatibility')]
#[ORM\UniqueConstraint(name: 'uniq_transplant_hla_locus', columns: ['transplant_id', 'hla_locus_id'])]
class TransplantHlaIncompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transplant::class, inversedBy: 'hlaIncompatibilities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Transplant $transplant = null;

    #[ORM\ManyToOne(targetEntity: HlaLocus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?HlaLocus $hlaLocus = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank(message: "Le nombre d'incompatibilités est obligatoire")]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur invalide (0, 1 ou 2)')]
    private ?int $incompatibilityCount = null;

    public function getId(): ?int { return $this->id; }

    public function getTransplant(): ?Transplant { return $this->transplant; }
    public function setTransplant(?Transplant $transplant): static { $this->transplant = $transplant; return $this; }

    public function getHlaLocus(): ?HlaLocus { return $this->hlaLocus; }
    public function setHlaLocus(?HlaLocus $hlaLocus): static { $this->hlaLocus = $hlaLocus; return $this; }

    public function getIncompatibilityCount(): ?int { return $this->incompatibilityCount; }
    public function setIncompatibilityCount(?int $incompatibilityCount): static { $this->incompatibilityCount = $incompatibilityCount; return $this; }
}
