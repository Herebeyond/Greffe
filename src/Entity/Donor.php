<?php

namespace App\Entity;

use App\Entity\Reference\BloodGroup;
use App\Entity\Reference\DeathCause;
use App\Entity\Reference\PerfusionLiquid;
use App\Entity\Reference\RelationshipType;
use App\Entity\Reference\SurgicalApproach;
use App\Entity\Reference\DonorType as DonorTypeRef;
use App\Repository\DonorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Donor entity - represents a kidney donor (living or deceased).
 * Can be linked to one or more Transplant entities.
 */
#[ORM\Entity(repositoryClass: DonorRepository::class)]
#[ORM\Table(name: 'donor')]
#[ORM\Index(columns: ['cristal_number'], name: 'idx_donor_cristal')]
class Donor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ===== Transplant relationship (inverse side) =====

    /** @var Collection<int, Transplant> */
    #[ORM\OneToMany(targetEntity: Transplant::class, mappedBy: 'donor')]
    private Collection $transplants;

    // ===== Common fields =====

    #[ORM\ManyToOne(targetEntity: DonorTypeRef::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type de donneur est obligatoire')]
    private ?DonorTypeRef $donorType = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le numéro CRISTAL est obligatoire')]
    private ?string $cristalNumber = null;

    #[ORM\ManyToOne(targetEntity: BloodGroup::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le groupe sanguin est obligatoire')]
    private ?BloodGroup $bloodGroup = null;

    #[ORM\Column(length: 1)]
    #[Assert\NotBlank(message: 'Le rhésus est obligatoire')]
    #[Assert\Choice(choices: ['+', '-'], message: 'Rhésus invalide')]
    private ?string $rhesus = null;

    #[ORM\Column(length: 1)]
    #[Assert\NotBlank(message: 'Le sexe est obligatoire')]
    #[Assert\Choice(choices: ['M', 'F'], message: 'Sexe invalide')]
    private ?string $sex = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: "L'âge est obligatoire")]
    #[Assert\Range(min: 0, max: 120, notInRangeMessage: "L'âge doit être compris entre {{ min }} et {{ max }}")]
    private ?int $age = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 50, max: 250, notInRangeMessage: 'La taille doit être comprise entre {{ min }} et {{ max }} cm')]
    private ?int $height = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 10, max: 300, notInRangeMessage: 'Le poids doit être compris entre {{ min }} et {{ max }} kg')]
    private ?int $weight = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $patientComment = null;

    // ===== HLA Grouping (junction table) =====

    /** @var Collection<int, DonorHlaTyping> */
    #[ORM\OneToMany(targetEntity: DonorHlaTyping::class, mappedBy: 'donor', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $hlaTypings;

    // ===== Serology (junction table) =====

    /** @var Collection<int, DonorSerology> */
    #[ORM\OneToMany(targetEntity: DonorSerology::class, mappedBy: 'donor', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $serologyResults;

    // ===== Surgical details =====

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $donorSurgeonName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $clampingDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $clampingTime = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: ['droit', 'gauche'], message: 'Côté de prélèvement invalide')]
    private ?string $donorHarvestSide = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mainArtery = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $upperPolarArtery = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lowerPolarArtery = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $vein = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $veinComment = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Assert\Choice(choices: ['Oui', 'Non'], message: 'Valeur machine de perfusion invalide')]
    private ?string $perfusionMachine = null;

    #[ORM\ManyToOne(targetEntity: PerfusionLiquid::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PerfusionLiquid $perfusionLiquid = null;

    // ===== Living donor specific =====

    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    private ?string $firstName = null;

    #[ORM\ManyToOne(targetEntity: RelationshipType::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?RelationshipType $relationshipType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $relationshipComment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $creatinine = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $isotopicClearance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $proteinuria = null;

    #[ORM\ManyToOne(targetEntity: SurgicalApproach::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SurgicalApproach $approach = null;

    #[ORM\Column(nullable: true)]
    private ?bool $robot = null;

    // ===== Deceased donor specific =====

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $originCity = null;

    #[ORM\ManyToOne(targetEntity: DeathCause::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DeathCause $deathCause = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deathCauseComment = null;

    #[ORM\Column(nullable: true)]
    private ?bool $extendedCriteriaDonor = null;

    #[ORM\Column(nullable: true)]
    private ?bool $cardiacArrest = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $cardiacArrestDuration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1, nullable: true)]
    private ?string $meanArterialPressure = null;

    #[ORM\Column(nullable: true)]
    private ?bool $amines = null;

    #[ORM\Column(nullable: true)]
    private ?bool $transfusion = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $cgr = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $cpa = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $pfc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $creatinineArrival = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $creatinineSample = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Assert\Choice(choices: ['1', '2'], message: 'Uretère invalide')]
    private ?string $ureter = null;

    #[ORM\ManyToOne(targetEntity: PerfusionLiquid::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PerfusionLiquid $conservationLiquid = null;

    // ===== Atheroma (deceased donor) =====

    #[ORM\Column(nullable: true)]
    private ?bool $aortaAtheroma = null;

    #[ORM\Column(nullable: true)]
    private ?bool $calcifiedAortaPlaques = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ostiumArteryAtheroma = null;

    #[ORM\Column(nullable: true)]
    private ?bool $calcifiedOstiumPlaques = null;

    #[ORM\Column(nullable: true)]
    private ?bool $renalArteryAtheroma = null;

    #[ORM\Column(nullable: true)]
    private ?bool $calcifiedRenalPlaques = null;

    #[ORM\Column(nullable: true)]
    private ?bool $digestiveWound = null;

    #[ORM\Column(nullable: true)]
    private ?bool $conservationLiquidInfection = null;

    // ===== Timestamps =====

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->hlaTypings = new ArrayCollection();
        $this->serologyResults = new ArrayCollection();
        $this->transplants = new ArrayCollection();
    }

    // ===== Getters & Setters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Transplant>
     */
    public function getTransplants(): Collection
    {
        return $this->transplants;
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

    public function getCristalNumber(): ?string
    {
        return $this->cristalNumber;
    }

    public function setCristalNumber(?string $cristalNumber): static
    {
        $this->cristalNumber = $cristalNumber;

        return $this;
    }

    public function getBloodGroup(): ?BloodGroup
    {
        return $this->bloodGroup;
    }

    public function setBloodGroup(?BloodGroup $bloodGroup): static
    {
        $this->bloodGroup = $bloodGroup;

        return $this;
    }

    public function getRhesus(): ?string
    {
        return $this->rhesus;
    }

    public function setRhesus(?string $rhesus): static
    {
        $this->rhesus = $rhesus;

        return $this;
    }

    /**
     * Full blood group with rhesus (e.g. "A+", "O-").
     */
    public function getFullBloodGroup(): string
    {
        return ($this->bloodGroup?->getCode() ?? '') . ($this->rhesus ?? '');
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): static
    {
        $this->sex = $sex;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPatientComment(): ?string
    {
        return $this->patientComment;
    }

    public function setPatientComment(?string $patientComment): static
    {
        $this->patientComment = $patientComment;

        return $this;
    }

    // ===== HLA (collection) =====

    /** @return Collection<int, DonorHlaTyping> */
    public function getHlaTypings(): Collection
    {
        return $this->hlaTypings;
    }

    public function addHlaTyping(DonorHlaTyping $typing): static
    {
        if (!$this->hlaTypings->contains($typing)) {
            $this->hlaTypings->add($typing);
            $typing->setDonor($this);
        }
        return $this;
    }

    public function removeHlaTyping(DonorHlaTyping $typing): static
    {
        if ($this->hlaTypings->removeElement($typing)) {
            if ($typing->getDonor() === $this) {
                $typing->setDonor(null);
            }
        }
        return $this;
    }

    public function getHlaValueByCode(string $code): ?int
    {
        foreach ($this->hlaTypings as $typing) {
            if ($typing->getHlaLocus()?->getCode() === $code) {
                return $typing->getValue();
            }
        }
        return null;
    }

    // ===== Serology (collection) =====

    /** @return Collection<int, DonorSerology> */
    public function getSerologyResults(): Collection
    {
        return $this->serologyResults;
    }

    public function addSerologyResult(DonorSerology $result): static
    {
        if (!$this->serologyResults->contains($result)) {
            $this->serologyResults->add($result);
            $result->setDonor($this);
        }
        return $this;
    }

    public function removeSerologyResult(DonorSerology $result): static
    {
        if ($this->serologyResults->removeElement($result)) {
            if ($result->getDonor() === $this) {
                $result->setDonor(null);
            }
        }
        return $this;
    }

    public function getSerologyResultByCode(string $code): ?string
    {
        foreach ($this->serologyResults as $result) {
            if ($result->getSerologyMarker()?->getCode() === $code) {
                return $result->getResult();
            }
        }
        return null;
    }

    // ===== Surgical details =====

    public function getDonorSurgeonName(): ?string
    {
        return $this->donorSurgeonName;
    }

    public function setDonorSurgeonName(?string $donorSurgeonName): static
    {
        $this->donorSurgeonName = $donorSurgeonName;

        return $this;
    }

    public function getClampingDate(): ?\DateTimeInterface
    {
        return $this->clampingDate;
    }

    public function setClampingDate(?\DateTimeInterface $clampingDate): static
    {
        $this->clampingDate = $clampingDate;

        return $this;
    }

    public function getClampingTime(): ?\DateTimeInterface
    {
        return $this->clampingTime;
    }

    public function setClampingTime(?\DateTimeInterface $clampingTime): static
    {
        $this->clampingTime = $clampingTime;

        return $this;
    }

    public function getDonorHarvestSide(): ?string
    {
        return $this->donorHarvestSide;
    }

    public function setDonorHarvestSide(?string $donorHarvestSide): static
    {
        $this->donorHarvestSide = $donorHarvestSide;

        return $this;
    }

    public function getMainArtery(): ?string
    {
        return $this->mainArtery;
    }

    public function setMainArtery(?string $mainArtery): static
    {
        $this->mainArtery = $mainArtery;

        return $this;
    }

    public function getUpperPolarArtery(): ?string
    {
        return $this->upperPolarArtery;
    }

    public function setUpperPolarArtery(?string $upperPolarArtery): static
    {
        $this->upperPolarArtery = $upperPolarArtery;

        return $this;
    }

    public function getLowerPolarArtery(): ?string
    {
        return $this->lowerPolarArtery;
    }

    public function setLowerPolarArtery(?string $lowerPolarArtery): static
    {
        $this->lowerPolarArtery = $lowerPolarArtery;

        return $this;
    }

    public function getVein(): ?string
    {
        return $this->vein;
    }

    public function setVein(?string $vein): static
    {
        $this->vein = $vein;

        return $this;
    }

    public function getVeinComment(): ?string
    {
        return $this->veinComment;
    }

    public function setVeinComment(?string $veinComment): static
    {
        $this->veinComment = $veinComment;

        return $this;
    }

    public function getPerfusionMachine(): ?string
    {
        return $this->perfusionMachine;
    }

    public function setPerfusionMachine(?string $perfusionMachine): static
    {
        $this->perfusionMachine = $perfusionMachine;

        return $this;
    }

    public function getPerfusionLiquid(): ?PerfusionLiquid
    {
        return $this->perfusionLiquid;
    }

    public function setPerfusionLiquid(?PerfusionLiquid $perfusionLiquid): static
    {
        $this->perfusionLiquid = $perfusionLiquid;

        return $this;
    }

    // ===== Living donor specific =====

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFullName(): string
    {
        if ($this->lastName && $this->firstName) {
            return $this->firstName . ' ' . $this->lastName;
        }

        return $this->cristalNumber ?? 'Donneur #' . $this->id;
    }

    public function getDisplayName(): string
    {
        if ($this->donorType?->getCode() === 'living' && $this->lastName) {
            return $this->getFullName();
        }

        return $this->cristalNumber ?? 'Donneur #' . $this->id;
    }

    public function getRelationshipType(): ?RelationshipType
    {
        return $this->relationshipType;
    }

    public function setRelationshipType(?RelationshipType $relationshipType): static
    {
        $this->relationshipType = $relationshipType;

        return $this;
    }

    public function getRelationshipComment(): ?string
    {
        return $this->relationshipComment;
    }

    public function setRelationshipComment(?string $relationshipComment): static
    {
        $this->relationshipComment = $relationshipComment;

        return $this;
    }

    public function getCreatinine(): ?string
    {
        return $this->creatinine;
    }

    public function setCreatinine(?string $creatinine): static
    {
        $this->creatinine = $creatinine;

        return $this;
    }

    public function getIsotopicClearance(): ?string
    {
        return $this->isotopicClearance;
    }

    public function setIsotopicClearance(?string $isotopicClearance): static
    {
        $this->isotopicClearance = $isotopicClearance;

        return $this;
    }

    public function getProteinuria(): ?string
    {
        return $this->proteinuria;
    }

    public function setProteinuria(?string $proteinuria): static
    {
        $this->proteinuria = $proteinuria;

        return $this;
    }

    public function getApproach(): ?SurgicalApproach
    {
        return $this->approach;
    }

    public function setApproach(?SurgicalApproach $approach): static
    {
        $this->approach = $approach;

        return $this;
    }

    public function isRobot(): ?bool
    {
        return $this->robot;
    }

    public function setRobot(?bool $robot): static
    {
        $this->robot = $robot;

        return $this;
    }

    /**
     * Calculate BMI: weight / (height/100)²
     */
    public function getBmi(): ?float
    {
        if ($this->weight && $this->height && $this->height > 0) {
            return round($this->weight / (($this->height / 100) ** 2), 1);
        }

        return null;
    }

    /**
     * MDRD clearance: 186 × (creatinine_µmol × 0.0113)^(-1.154) × age^(-0.203) × 0.742 (if female)
     */
    public function getCalculatedClearance(): ?float
    {
        $creat = (float) $this->creatinine;
        if ($creat <= 0 || !$this->age) {
            return null;
        }

        $clearance = 186 * (($creat * 0.0113) ** (-1.154)) * ($this->age ** (-0.203));
        if ($this->sex === 'F') {
            $clearance *= 0.742;
        }

        return round($clearance, 1);
    }

    // ===== Deceased donor specific =====

    public function getOriginCity(): ?string
    {
        return $this->originCity;
    }

    public function setOriginCity(?string $originCity): static
    {
        $this->originCity = $originCity;

        return $this;
    }

    public function getDeathCause(): ?DeathCause
    {
        return $this->deathCause;
    }

    public function setDeathCause(?DeathCause $deathCause): static
    {
        $this->deathCause = $deathCause;

        return $this;
    }

    public function getDeathCauseComment(): ?string
    {
        return $this->deathCauseComment;
    }

    public function setDeathCauseComment(?string $deathCauseComment): static
    {
        $this->deathCauseComment = $deathCauseComment;

        return $this;
    }

    public function isExtendedCriteriaDonor(): ?bool
    {
        return $this->extendedCriteriaDonor;
    }

    public function setExtendedCriteriaDonor(?bool $extendedCriteriaDonor): static
    {
        $this->extendedCriteriaDonor = $extendedCriteriaDonor;

        return $this;
    }

    public function isCardiacArrest(): ?bool
    {
        return $this->cardiacArrest;
    }

    public function setCardiacArrest(?bool $cardiacArrest): static
    {
        $this->cardiacArrest = $cardiacArrest;

        return $this;
    }

    public function getCardiacArrestDuration(): ?int
    {
        return $this->cardiacArrestDuration;
    }

    public function setCardiacArrestDuration(?int $cardiacArrestDuration): static
    {
        $this->cardiacArrestDuration = $cardiacArrestDuration;

        return $this;
    }

    public function getMeanArterialPressure(): ?string
    {
        return $this->meanArterialPressure;
    }

    public function setMeanArterialPressure(?string $meanArterialPressure): static
    {
        $this->meanArterialPressure = $meanArterialPressure;

        return $this;
    }

    public function isAmines(): ?bool
    {
        return $this->amines;
    }

    public function setAmines(?bool $amines): static
    {
        $this->amines = $amines;

        return $this;
    }

    public function isTransfusion(): ?bool
    {
        return $this->transfusion;
    }

    public function setTransfusion(?bool $transfusion): static
    {
        $this->transfusion = $transfusion;

        return $this;
    }

    public function getCgr(): ?int
    {
        return $this->cgr;
    }

    public function setCgr(?int $cgr): static
    {
        $this->cgr = $cgr;

        return $this;
    }

    public function getCpa(): ?int
    {
        return $this->cpa;
    }

    public function setCpa(?int $cpa): static
    {
        $this->cpa = $cpa;

        return $this;
    }

    public function getPfc(): ?int
    {
        return $this->pfc;
    }

    public function setPfc(?int $pfc): static
    {
        $this->pfc = $pfc;

        return $this;
    }

    public function getCreatinineArrival(): ?string
    {
        return $this->creatinineArrival;
    }

    public function setCreatinineArrival(?string $creatinineArrival): static
    {
        $this->creatinineArrival = $creatinineArrival;

        return $this;
    }

    public function getCreatinineSample(): ?string
    {
        return $this->creatinineSample;
    }

    public function setCreatinineSample(?string $creatinineSample): static
    {
        $this->creatinineSample = $creatinineSample;

        return $this;
    }

    /**
     * DFG/GFR formula:
     * Male: 175 × (creatinine / 88.4)^(-1.154) × age^(-0.203)
     * Female: × 0.742
     */
    public function getDfg(): ?float
    {
        $creat = (float) $this->creatinineSample;
        if ($creat <= 0 || !$this->age) {
            return null;
        }

        $dfg = 175 * (($creat / 88.4) ** (-1.154)) * ($this->age ** (-0.203));
        if ($this->sex === 'F') {
            $dfg *= 0.742;
        }

        return round($dfg, 1);
    }

    public function getUreter(): ?string
    {
        return $this->ureter;
    }

    public function setUreter(?string $ureter): static
    {
        $this->ureter = $ureter;

        return $this;
    }

    public function getConservationLiquid(): ?PerfusionLiquid
    {
        return $this->conservationLiquid;
    }

    public function setConservationLiquid(?PerfusionLiquid $conservationLiquid): static
    {
        $this->conservationLiquid = $conservationLiquid;

        return $this;
    }

    // ===== Atheroma =====

    public function isAortaAtheroma(): ?bool
    {
        return $this->aortaAtheroma;
    }

    public function setAortaAtheroma(?bool $aortaAtheroma): static
    {
        $this->aortaAtheroma = $aortaAtheroma;

        return $this;
    }

    public function isCalcifiedAortaPlaques(): ?bool
    {
        return $this->calcifiedAortaPlaques;
    }

    public function setCalcifiedAortaPlaques(?bool $calcifiedAortaPlaques): static
    {
        $this->calcifiedAortaPlaques = $calcifiedAortaPlaques;

        return $this;
    }

    public function isOstiumArteryAtheroma(): ?bool
    {
        return $this->ostiumArteryAtheroma;
    }

    public function setOstiumArteryAtheroma(?bool $ostiumArteryAtheroma): static
    {
        $this->ostiumArteryAtheroma = $ostiumArteryAtheroma;

        return $this;
    }

    public function isCalcifiedOstiumPlaques(): ?bool
    {
        return $this->calcifiedOstiumPlaques;
    }

    public function setCalcifiedOstiumPlaques(?bool $calcifiedOstiumPlaques): static
    {
        $this->calcifiedOstiumPlaques = $calcifiedOstiumPlaques;

        return $this;
    }

    public function isRenalArteryAtheroma(): ?bool
    {
        return $this->renalArteryAtheroma;
    }

    public function setRenalArteryAtheroma(?bool $renalArteryAtheroma): static
    {
        $this->renalArteryAtheroma = $renalArteryAtheroma;

        return $this;
    }

    public function isCalcifiedRenalPlaques(): ?bool
    {
        return $this->calcifiedRenalPlaques;
    }

    public function setCalcifiedRenalPlaques(?bool $calcifiedRenalPlaques): static
    {
        $this->calcifiedRenalPlaques = $calcifiedRenalPlaques;

        return $this;
    }

    public function isDigestiveWound(): ?bool
    {
        return $this->digestiveWound;
    }

    public function setDigestiveWound(?bool $digestiveWound): static
    {
        $this->digestiveWound = $digestiveWound;

        return $this;
    }

    public function isConservationLiquidInfection(): ?bool
    {
        return $this->conservationLiquidInfection;
    }

    public function setConservationLiquidInfection(?bool $conservationLiquidInfection): static
    {
        $this->conservationLiquidInfection = $conservationLiquidInfection;

        return $this;
    }

    // ===== Timestamps =====

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
