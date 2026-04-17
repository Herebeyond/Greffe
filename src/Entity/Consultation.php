<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Reference\ConsultationType as ConsultationTypeRef;
use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiFilter(SearchFilter::class, properties: ['patient' => 'exact'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
        new Get(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
        new Post(security: "is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')"),
        new Put(security: "(is_granted('ROLE_DOCTOR') or is_granted('ROLE_NURSE')) and object.getCreatedBy() == user"),
    ],
    normalizationContext: ['groups' => ['consultation:read']],
    denormalizationContext: ['groups' => ['consultation:write']],
    paginationItemsPerPage: 20,
)]
#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['consultation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['consultation:read'])]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de consultation est obligatoire')]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100)]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?string $practitionerName = null;

    #[ORM\ManyToOne(targetEntity: ConsultationTypeRef::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type de consultation est obligatoire')]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?ConsultationTypeRef $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Les observations sont obligatoires')]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?string $observations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?string $treatmentNotes = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['consultation:read', 'consultation:write'])]
    private ?\DateTimeInterface $nextAppointmentDate = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['consultation:read'])]
    private array $attachmentFilenames = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['consultation:read'])]
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    #[Groups(['consultation:read'])]
    public function getCreatedByName(): ?string
    {
        return $this->createdBy?->getFullName();
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

    public function getPractitionerName(): ?string
    {
        return $this->practitionerName;
    }

    public function setPractitionerName(string $practitionerName): static
    {
        $this->practitionerName = $practitionerName;

        return $this;
    }

    public function getType(): ?ConsultationTypeRef
    {
        return $this->type;
    }

    public function setType(?ConsultationTypeRef $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function getTreatmentNotes(): ?string
    {
        return $this->treatmentNotes;
    }

    public function setTreatmentNotes(?string $treatmentNotes): static
    {
        $this->treatmentNotes = $treatmentNotes;

        return $this;
    }

    public function getNextAppointmentDate(): ?\DateTimeInterface
    {
        return $this->nextAppointmentDate;
    }

    public function setNextAppointmentDate(?\DateTimeInterface $nextAppointmentDate): static
    {
        $this->nextAppointmentDate = $nextAppointmentDate;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAttachmentFilenames(): array
    {
        return $this->attachmentFilenames;
    }

    public function setAttachmentFilenames(array $attachmentFilenames): static
    {
        $this->attachmentFilenames = $attachmentFilenames;
        return $this;
    }

    public function addAttachmentFilename(string $filename): static
    {
        if (!in_array($filename, $this->attachmentFilenames, true)) {
            $this->attachmentFilenames[] = $filename;
        }
        return $this;
    }

    public function removeAttachmentFilename(string $filename): static
    {
        $this->attachmentFilenames = array_values(array_filter(
            $this->attachmentFilenames,
            fn (string $f) => $f !== $filename
        ));
        return $this;
    }
}
