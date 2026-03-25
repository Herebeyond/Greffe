<?php

namespace App\Entity;

use App\Entity\Reference\DonorType as DonorTypeRef;
use App\Entity\Reference\ImmunologicalRisk;
use App\Entity\Reference\ImmunosuppressiveDrug;
use App\Entity\Reference\PeritonealPosition;
use App\Entity\Reference\TransplantType as TransplantTypeRef;
use App\Repository\TransplantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Transplant entity - represents a kidney transplant (greffe rénale).
 * Links a patient (recipient) to a graft from a donor.
 */
#[ORM\Entity(repositoryClass: TransplantRepository::class)]
#[ORM\Table(name: 'transplant')]
#[ORM\Index(columns: ['patient_id'], name: 'idx_transplant_patient')]
class Transplant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    // ===== Essential information =====

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de greffe est obligatoire')]
    private ?\DateTimeInterface $transplantDate = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'Le rang de greffe est obligatoire')]
    #[Assert\Positive(message: 'Le rang doit être un nombre positif')]
    private ?int $rank = null;

    #[ORM\ManyToOne(targetEntity: DonorTypeRef::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type de donneur est obligatoire')]
    private ?DonorTypeRef $donorType = null;

    // ===== Graft details =====

    #[ORM\Column]
    private bool $isGraftFunctional = true;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $graftEndDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $graftEndCause = null;

    #[ORM\ManyToOne(targetEntity: TransplantTypeRef::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type de transplantation est obligatoire')]
    private ?TransplantTypeRef $transplantType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $surgeonName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $declampingDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $declampingTime = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le côté de prélèvement est obligatoire')]
    #[Assert\Choice(choices: ['droit', 'gauche'], message: 'Côté de prélèvement invalide')]
    private ?string $harvestSide = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le côté de transplantation est obligatoire')]
    #[Assert\Choice(choices: ['droit', 'gauche'], message: 'Côté de transplantation invalide')]
    private ?string $transplantSide = null;

    #[ORM\ManyToOne(targetEntity: PeritonealPosition::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'La position péritonéale est obligatoire')]
    private ?PeritonealPosition $peritonealPosition = null;

    /** Total ischemia in minutes (stored as integer, displayed as HH:MM). */
    #[ORM\Column]
    #[Assert\NotBlank(message: "La durée d'ischémie totale est obligatoire")]
    #[Assert\Positive(message: "La durée d'ischémie doit être positive")]
    private ?int $totalIschemiaMinutes = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La durée d'anastomose est obligatoire")]
    #[Assert\Positive(message: "La durée d'anastomose doit être positive")]
    private ?int $anastomosisDuration = null;

    #[ORM\Column]
    private bool $jjProbe = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $operativeReportFilename = null;

    // ===== Virological status (junction table) =====

    /** @var Collection<int, TransplantVirologicalStatus> */
    #[ORM\OneToMany(targetEntity: TransplantVirologicalStatus::class, mappedBy: 'transplant', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $virologicalStatuses;

    // ===== HLA incompatibility (junction table) =====

    /** @var Collection<int, TransplantHlaIncompatibility> */
    #[ORM\OneToMany(targetEntity: TransplantHlaIncompatibility::class, mappedBy: 'transplant', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $hlaIncompatibilities;

    // ===== Immunological risk =====

    #[ORM\ManyToOne(targetEntity: ImmunologicalRisk::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le risque immunologique est obligatoire')]
    private ?ImmunologicalRisk $immunologicalRisk = null;

    // ===== Immunosuppressive conditioning (ManyToMany) =====

    /** @var Collection<int, ImmunosuppressiveDrug> */
    #[ORM\ManyToMany(targetEntity: ImmunosuppressiveDrug::class)]
    #[ORM\JoinTable(name: 'transplant_immunosuppressive_drug')]
    private Collection $immunosuppressiveDrugs;

    // ===== Dialysis =====

    #[ORM\Column]
    private bool $dialysis = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastDialysisDate = null;

    // ===== Protocol =====

    #[ORM\Column]
    private bool $hasProtocol = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $protocolFilename = null;

    // ===== Donor data (JSON - legacy) =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $donorData = null;

    // ===== Donor relationship =====

    #[ORM\ManyToOne(targetEntity: Donor::class, inversedBy: 'transplants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Donor $donor = null;

    // ===== Timestamps =====

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->virologicalStatuses = new ArrayCollection();
        $this->hlaIncompatibilities = new ArrayCollection();
        $this->immunosuppressiveDrugs = new ArrayCollection();
    }

    // ===== Getters & Setters =====

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

    public function getTransplantDate(): ?\DateTimeInterface
    {
        return $this->transplantDate;
    }

    public function setTransplantDate(?\DateTimeInterface $transplantDate): static
    {
        $this->transplantDate = $transplantDate;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): static
    {
        $this->rank = $rank;

        return $this;
    }

    public function getDonorType(): ?DonorTypeRef
    {
        return $this->donorType;
    }

    public function setDonorType(?DonorTypeRef $donorType): static
    {
        $this->donorType = $donorType;

        return $this;
    }

    public function getDonorTypeLabel(): string
    {
        return $this->donorType?->getLabel() ?? '';
    }

    public function isGraftFunctional(): bool
    {
        return $this->isGraftFunctional;
    }

    public function setIsGraftFunctional(bool $isGraftFunctional): static
    {
        $this->isGraftFunctional = $isGraftFunctional;

        return $this;
    }

    public function getGraftEndDate(): ?\DateTimeInterface
    {
        return $this->graftEndDate;
    }

    public function setGraftEndDate(?\DateTimeInterface $graftEndDate): static
    {
        $this->graftEndDate = $graftEndDate;

        return $this;
    }

    public function getGraftEndCause(): ?string
    {
        return $this->graftEndCause;
    }

    public function setGraftEndCause(?string $graftEndCause): static
    {
        $this->graftEndCause = $graftEndCause;

        return $this;
    }

    public function getTransplantType(): ?TransplantTypeRef
    {
        return $this->transplantType;
    }

    public function setTransplantType(?TransplantTypeRef $transplantType): static
    {
        $this->transplantType = $transplantType;

        return $this;
    }

    public function getSurgeonName(): ?string
    {
        return $this->surgeonName;
    }

    public function setSurgeonName(?string $surgeonName): static
    {
        $this->surgeonName = $surgeonName;

        return $this;
    }

    public function getDeclampingDate(): ?\DateTimeInterface
    {
        return $this->declampingDate;
    }

    public function setDeclampingDate(?\DateTimeInterface $declampingDate): static
    {
        $this->declampingDate = $declampingDate;

        return $this;
    }

    public function getDeclampingTime(): ?\DateTimeInterface
    {
        return $this->declampingTime;
    }

    public function setDeclampingTime(?\DateTimeInterface $declampingTime): static
    {
        $this->declampingTime = $declampingTime;

        return $this;
    }

    public function getHarvestSide(): ?string
    {
        return $this->harvestSide;
    }

    public function setHarvestSide(?string $harvestSide): static
    {
        $this->harvestSide = $harvestSide;

        return $this;
    }

    public function getTransplantSide(): ?string
    {
        return $this->transplantSide;
    }

    public function setTransplantSide(?string $transplantSide): static
    {
        $this->transplantSide = $transplantSide;

        return $this;
    }

    public function getPeritonealPosition(): ?PeritonealPosition
    {
        return $this->peritonealPosition;
    }

    public function setPeritonealPosition(?PeritonealPosition $peritonealPosition): static
    {
        $this->peritonealPosition = $peritonealPosition;

        return $this;
    }

    public function getTotalIschemiaMinutes(): ?int
    {
        return $this->totalIschemiaMinutes;
    }

    public function setTotalIschemiaMinutes(?int $totalIschemiaMinutes): static
    {
        $this->totalIschemiaMinutes = $totalIschemiaMinutes;

        return $this;
    }

    /**
     * Returns total ischemia formatted as HH:MM.
     */
    public function getTotalIschemiaFormatted(): string
    {
        if ($this->totalIschemiaMinutes === null) {
            return '';
        }

        $hours = intdiv($this->totalIschemiaMinutes, 60);
        $minutes = $this->totalIschemiaMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getAnastomosisDuration(): ?int
    {
        return $this->anastomosisDuration;
    }

    public function setAnastomosisDuration(?int $anastomosisDuration): static
    {
        $this->anastomosisDuration = $anastomosisDuration;

        return $this;
    }

    public function isJjProbe(): bool
    {
        return $this->jjProbe;
    }

    public function setJjProbe(bool $jjProbe): static
    {
        $this->jjProbe = $jjProbe;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getOperativeReportFilename(): ?string
    {
        return $this->operativeReportFilename;
    }

    public function setOperativeReportFilename(?string $operativeReportFilename): static
    {
        $this->operativeReportFilename = $operativeReportFilename;

        return $this;
    }

    // ===== Virological statuses (collection) =====

    /** @return Collection<int, TransplantVirologicalStatus> */
    public function getVirologicalStatuses(): Collection
    {
        return $this->virologicalStatuses;
    }

    public function addVirologicalStatus(TransplantVirologicalStatus $status): static
    {
        if (!$this->virologicalStatuses->contains($status)) {
            $this->virologicalStatuses->add($status);
            $status->setTransplant($this);
        }
        return $this;
    }

    public function removeVirologicalStatus(TransplantVirologicalStatus $status): static
    {
        if ($this->virologicalStatuses->removeElement($status)) {
            if ($status->getTransplant() === $this) {
                $status->setTransplant(null);
            }
        }
        return $this;
    }

    public function getVirologicalStatusByCode(string $code): ?string
    {
        foreach ($this->virologicalStatuses as $status) {
            if ($status->getVirologicalMarker()?->getCode() === $code) {
                return $status->getStatus();
            }
        }
        return null;
    }

    // ===== HLA incompatibilities (collection) =====

    /** @return Collection<int, TransplantHlaIncompatibility> */
    public function getHlaIncompatibilities(): Collection
    {
        return $this->hlaIncompatibilities;
    }

    public function addHlaIncompatibility(TransplantHlaIncompatibility $incompatibility): static
    {
        if (!$this->hlaIncompatibilities->contains($incompatibility)) {
            $this->hlaIncompatibilities->add($incompatibility);
            $incompatibility->setTransplant($this);
        }
        return $this;
    }

    public function removeHlaIncompatibility(TransplantHlaIncompatibility $incompatibility): static
    {
        if ($this->hlaIncompatibilities->removeElement($incompatibility)) {
            if ($incompatibility->getTransplant() === $this) {
                $incompatibility->setTransplant(null);
            }
        }
        return $this;
    }

    public function getHlaIncompatibilityByCode(string $code): ?int
    {
        foreach ($this->hlaIncompatibilities as $incomp) {
            if ($incomp->getHlaLocus()?->getCode() === $code) {
                return $incomp->getIncompatibilityCount();
            }
        }
        return null;
    }

    public function getImmunologicalRisk(): ?ImmunologicalRisk
    {
        return $this->immunologicalRisk;
    }

    public function setImmunologicalRisk(?ImmunologicalRisk $immunologicalRisk): static
    {
        $this->immunologicalRisk = $immunologicalRisk;

        return $this;
    }

    public function getImmunologicalRiskClass(): string
    {
        return $this->immunologicalRisk?->getColorClass() ?? '';
    }

    // ===== Immunosuppressive drugs (ManyToMany) =====

    /** @return Collection<int, ImmunosuppressiveDrug> */
    public function getImmunosuppressiveDrugs(): Collection
    {
        return $this->immunosuppressiveDrugs;
    }

    public function addImmunosuppressiveDrug(ImmunosuppressiveDrug $drug): static
    {
        if (!$this->immunosuppressiveDrugs->contains($drug)) {
            $this->immunosuppressiveDrugs->add($drug);
        }
        return $this;
    }

    public function removeImmunosuppressiveDrug(ImmunosuppressiveDrug $drug): static
    {
        $this->immunosuppressiveDrugs->removeElement($drug);
        return $this;
    }

    public function isDialysis(): bool
    {
        return $this->dialysis;
    }

    public function setDialysis(bool $dialysis): static
    {
        $this->dialysis = $dialysis;

        return $this;
    }

    public function getLastDialysisDate(): ?\DateTimeInterface
    {
        return $this->lastDialysisDate;
    }

    public function setLastDialysisDate(?\DateTimeInterface $lastDialysisDate): static
    {
        $this->lastDialysisDate = $lastDialysisDate;

        return $this;
    }

    public function isHasProtocol(): bool
    {
        return $this->hasProtocol;
    }

    public function setHasProtocol(bool $hasProtocol): static
    {
        $this->hasProtocol = $hasProtocol;

        return $this;
    }

    public function getProtocolFilename(): ?string
    {
        return $this->protocolFilename;
    }

    public function setProtocolFilename(?string $protocolFilename): static
    {
        $this->protocolFilename = $protocolFilename;

        return $this;
    }

    public function getDonorData(): ?array
    {
        return $this->donorData;
    }

    public function setDonorData(?array $donorData): static
    {
        $this->donorData = $donorData;

        return $this;
    }

    public function getDonor(): ?Donor
    {
        return $this->donor;
    }

    public function setDonor(?Donor $donor): static
    {
        $this->donor = $donor;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
