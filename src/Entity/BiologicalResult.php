<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\BiologicalResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiFilter(SearchFilter::class, properties: ['patient' => 'exact'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
        new Get(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
    ],
    normalizationContext: ['groups' => ['bio:read']],
    paginationItemsPerPage: 20,
)]
#[ORM\Entity(repositoryClass: BiologicalResultRepository::class)]
#[ORM\Table(name: 'biological_result')]
class BiologicalResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['bio:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['bio:read'])]
    private ?Patient $patient = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date du prélèvement est obligatoire')]
    #[Groups(['bio:read'])]
    private ?\DateTimeInterface $date = null;

    /** Créatinine sérique (µmol/L) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $creatinine = null;

    /** Clairance de la créatinine (mL/min) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $creatinineClearance = null;

    /** Protéinurie (g/24h) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $proteinuria = null;

    /** Hémoglobine (g/dL) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $hemoglobin = null;

    /** Leucocytes (G/L) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $whiteBloodCells = null;

    /** Plaquettes (G/L) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $platelets = null;

    /** Taux résiduel de tacrolimus (ng/mL) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $tacrolimusLevel = null;

    /** Taux résiduel de ciclosporine (ng/mL) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?float $ciclosporinLevel = null;

    /** PCR CMV */
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['Positif', 'Négatif', 'Non effectué'], message: 'Valeur PCR CMV invalide')]
    #[Groups(['bio:read'])]
    private ?string $cmvPcr = null;

    /** PCR EBV */
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['Positif', 'Négatif', 'Non effectué'], message: 'Valeur PCR EBV invalide')]
    #[Groups(['bio:read'])]
    private ?string $ebvPcr = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['bio:read'])]
    private ?string $comment = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['bio:read'])]
    private array $reportFilenames = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['bio:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCreatinine(): ?float
    {
        return $this->creatinine;
    }

    public function setCreatinine(?float $creatinine): static
    {
        $this->creatinine = $creatinine;

        return $this;
    }

    public function getCreatinineClearance(): ?float
    {
        return $this->creatinineClearance;
    }

    public function setCreatinineClearance(?float $creatinineClearance): static
    {
        $this->creatinineClearance = $creatinineClearance;

        return $this;
    }

    public function getProteinuria(): ?float
    {
        return $this->proteinuria;
    }

    public function setProteinuria(?float $proteinuria): static
    {
        $this->proteinuria = $proteinuria;

        return $this;
    }

    public function getHemoglobin(): ?float
    {
        return $this->hemoglobin;
    }

    public function setHemoglobin(?float $hemoglobin): static
    {
        $this->hemoglobin = $hemoglobin;

        return $this;
    }

    public function getWhiteBloodCells(): ?float
    {
        return $this->whiteBloodCells;
    }

    public function setWhiteBloodCells(?float $whiteBloodCells): static
    {
        $this->whiteBloodCells = $whiteBloodCells;

        return $this;
    }

    public function getPlatelets(): ?float
    {
        return $this->platelets;
    }

    public function setPlatelets(?float $platelets): static
    {
        $this->platelets = $platelets;

        return $this;
    }

    public function getTacrolimusLevel(): ?float
    {
        return $this->tacrolimusLevel;
    }

    public function setTacrolimusLevel(?float $tacrolimusLevel): static
    {
        $this->tacrolimusLevel = $tacrolimusLevel;

        return $this;
    }

    public function getCiclosporinLevel(): ?float
    {
        return $this->ciclosporinLevel;
    }

    public function setCiclosporinLevel(?float $ciclosporinLevel): static
    {
        $this->ciclosporinLevel = $ciclosporinLevel;

        return $this;
    }

    public function getCmvPcr(): ?string
    {
        return $this->cmvPcr;
    }

    public function setCmvPcr(?string $cmvPcr): static
    {
        $this->cmvPcr = $cmvPcr;

        return $this;
    }

    public function getEbvPcr(): ?string
    {
        return $this->ebvPcr;
    }

    public function setEbvPcr(?string $ebvPcr): static
    {
        $this->ebvPcr = $ebvPcr;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReportFilenames(): array
    {
        return $this->reportFilenames;
    }

    public function setReportFilenames(array $reportFilenames): static
    {
        $this->reportFilenames = $reportFilenames;
        return $this;
    }

    public function addReportFilename(string $filename): static
    {
        if (!in_array($filename, $this->reportFilenames, true)) {
            $this->reportFilenames[] = $filename;
        }
        return $this;
    }

    public function removeReportFilename(string $filename): static
    {
        $this->reportFilenames = array_values(array_filter(
            $this->reportFilenames,
            fn (string $f) => $f !== $filename
        ));
        return $this;
    }
}
