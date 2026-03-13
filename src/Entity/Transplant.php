<?php

namespace App\Entity;

use App\Repository\TransplantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Transplant entity - represents a kidney transplant (greffe rénale).
 * Links a patient (recipient) to a graft from a donor.
 * Donor data is stored as a JSON column to simplify the schema.
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

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le type de donneur est obligatoire')]
    #[Assert\Choice(
        choices: ['living', 'deceased_encephalic', 'deceased_cardiac_arrest'],
        message: 'Type de donneur invalide'
    )]
    private ?string $donorType = null;

    // ===== Graft details =====

    #[ORM\Column]
    private bool $isGraftFunctional = true;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $graftEndDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $graftEndCause = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de transplantation est obligatoire')]
    #[Assert\Choice(
        choices: ['Rein', 'Rein donneur vivant', 'Rein-pancréas', 'Rein-foie', 'Rein-coeur', 'Autre'],
        message: 'Type de transplantation invalide'
    )]
    private ?string $transplantType = null;

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

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'La position péritonéale est obligatoire')]
    #[Assert\Choice(
        choices: ['Extra Péritonéal', 'Intra Péritonéal'],
        message: 'Position péritonéale invalide'
    )]
    private ?string $peritonealPosition = null;

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

    // ===== Virological status =====

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le statut CMV est obligatoire')]
    #[Assert\Choice(choices: ['D-/R-', 'D-/R+', 'D+/R-', 'D+/R+'], message: 'Statut CMV invalide')]
    private ?string $cmvStatus = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: ['D-/R-', 'D-/R+', 'D+/R-', 'D+/R+'], message: 'Statut EBV invalide')]
    private ?string $ebvStatus = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Assert\Choice(choices: ['R+', 'R-'], message: 'Statut toxoplasmose invalide')]
    private ?string $toxoplasmosisStatus = null;

    // ===== HLA incompatibility =====

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: "L'incompatibilité HLA-A est obligatoire")]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-A invalide')]
    private ?int $hlaA = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: "L'incompatibilité HLA-B est obligatoire")]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-B invalide')]
    private ?int $hlaB = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-Cw invalide')]
    private ?int $hlaCw = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: "L'incompatibilité HLA-DR est obligatoire")]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-DR invalide')]
    private ?int $hlaDR = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: "L'incompatibilité HLA-DQ est obligatoire")]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-DQ invalide')]
    private ?int $hlaDQ = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Choice(choices: [0, 1, 2], message: 'Valeur HLA-DP invalide')]
    private ?int $hlaDP = null;

    // ===== Immunological risk =====

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le risque immunologique est obligatoire')]
    #[Assert\Choice(
        choices: ['Non immunisé', 'Immunisé sans DSA', 'Immunisé DSA', 'ABO incompatible'],
        message: 'Risque immunologique invalide'
    )]
    private ?string $immunologicalRisk = null;

    // ===== Immunosuppressive conditioning =====

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    private array $immunosuppressiveConditioning = [];

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

    // ===== Donor data (JSON) =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $donorData = null;

    // ===== Donor relationship =====

    #[ORM\ManyToOne(targetEntity: Donor::class)]
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

    public function getDonorType(): ?string
    {
        return $this->donorType;
    }

    public function setDonorType(?string $donorType): static
    {
        $this->donorType = $donorType;

        return $this;
    }

    public function getDonorTypeLabel(): string
    {
        return match ($this->donorType) {
            'living' => 'Donneur vivant',
            'deceased_encephalic' => 'Donneur décédé (mort encéphalique)',
            'deceased_cardiac_arrest' => 'Donneur décédé (arrêt cardiaque)',
            default => $this->donorType ?? '',
        };
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

    public function getTransplantType(): ?string
    {
        return $this->transplantType;
    }

    public function setTransplantType(?string $transplantType): static
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

    public function getPeritonealPosition(): ?string
    {
        return $this->peritonealPosition;
    }

    public function setPeritonealPosition(?string $peritonealPosition): static
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

    public function getCmvStatus(): ?string
    {
        return $this->cmvStatus;
    }

    public function setCmvStatus(?string $cmvStatus): static
    {
        $this->cmvStatus = $cmvStatus;

        return $this;
    }

    public function getEbvStatus(): ?string
    {
        return $this->ebvStatus;
    }

    public function setEbvStatus(?string $ebvStatus): static
    {
        $this->ebvStatus = $ebvStatus;

        return $this;
    }

    public function getToxoplasmosisStatus(): ?string
    {
        return $this->toxoplasmosisStatus;
    }

    public function setToxoplasmosisStatus(?string $toxoplasmosisStatus): static
    {
        $this->toxoplasmosisStatus = $toxoplasmosisStatus;

        return $this;
    }

    public function getHlaA(): ?int
    {
        return $this->hlaA;
    }

    public function setHlaA(?int $hlaA): static
    {
        $this->hlaA = $hlaA;

        return $this;
    }

    public function getHlaB(): ?int
    {
        return $this->hlaB;
    }

    public function setHlaB(?int $hlaB): static
    {
        $this->hlaB = $hlaB;

        return $this;
    }

    public function getHlaCw(): ?int
    {
        return $this->hlaCw;
    }

    public function setHlaCw(?int $hlaCw): static
    {
        $this->hlaCw = $hlaCw;

        return $this;
    }

    public function getHlaDR(): ?int
    {
        return $this->hlaDR;
    }

    public function setHlaDR(?int $hlaDR): static
    {
        $this->hlaDR = $hlaDR;

        return $this;
    }

    public function getHlaDQ(): ?int
    {
        return $this->hlaDQ;
    }

    public function setHlaDQ(?int $hlaDQ): static
    {
        $this->hlaDQ = $hlaDQ;

        return $this;
    }

    public function getHlaDP(): ?int
    {
        return $this->hlaDP;
    }

    public function setHlaDP(?int $hlaDP): static
    {
        $this->hlaDP = $hlaDP;

        return $this;
    }

    public function getImmunologicalRisk(): ?string
    {
        return $this->immunologicalRisk;
    }

    public function setImmunologicalRisk(?string $immunologicalRisk): static
    {
        $this->immunologicalRisk = $immunologicalRisk;

        return $this;
    }

    /**
     * Returns the CSS class for the immunological risk color coding.
     */
    public function getImmunologicalRiskClass(): string
    {
        return match ($this->immunologicalRisk) {
            'Non immunisé' => 'risk-green',
            'Immunisé sans DSA' => 'risk-orange',
            'Immunisé DSA', 'ABO incompatible' => 'risk-red',
            default => '',
        };
    }

    /** @return string[] */
    public function getImmunosuppressiveConditioning(): array
    {
        return $this->immunosuppressiveConditioning;
    }

    /** @param string[] $immunosuppressiveConditioning */
    public function setImmunosuppressiveConditioning(array $immunosuppressiveConditioning): static
    {
        $this->immunosuppressiveConditioning = $immunosuppressiveConditioning;

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
