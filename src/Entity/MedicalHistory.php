<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Reference\MedicalHistoryType as MedicalHistoryTypeRef;
use App\Repository\MedicalHistoryRepository;
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
    normalizationContext: ['groups' => ['medical_history:read']],
    paginationItemsPerPage: 20,
)]
#[ORM\Entity(repositoryClass: MedicalHistoryRepository::class)]
#[ORM\Table(name: 'medical_history')]
class MedicalHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['medical_history:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['medical_history:read'])]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: MedicalHistoryTypeRef::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type d\'antécédent est obligatoire')]
    #[Groups(['medical_history:read'])]
    private ?MedicalHistoryTypeRef $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Groups(['medical_history:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['medical_history:read'])]
    private ?\DateTimeInterface $diagnosisDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medical_history:read'])]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['medical_history:read'])]
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

    public function getType(): ?MedicalHistoryTypeRef
    {
        return $this->type;
    }

    public function setType(?MedicalHistoryTypeRef $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDiagnosisDate(): ?\DateTimeInterface
    {
        return $this->diagnosisDate;
    }

    public function setDiagnosisDate(?\DateTimeInterface $diagnosisDate): static
    {
        $this->diagnosisDate = $diagnosisDate;

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
}
