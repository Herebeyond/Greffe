<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Reference\EducationTopic;
use App\Entity\Reference\PatientProgress as PatientProgressRef;
use App\Repository\TherapeuticEducationRepository;
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
    normalizationContext: ['groups' => ['etp:read']],
    paginationItemsPerPage: 20,
)]
#[ORM\Entity(repositoryClass: TherapeuticEducationRepository::class)]
#[ORM\Table(name: 'therapeutic_education')]
class TherapeuticEducation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etp:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['etp:read'])]
    private ?Patient $patient = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de la séance est obligatoire')]
    #[Groups(['etp:read'])]
    private ?\DateTimeInterface $sessionDate = null;

    #[ORM\ManyToOne(targetEntity: EducationTopic::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le thème est obligatoire')]
    #[Groups(['etp:read'])]
    private ?EducationTopic $topic = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de l\'éducateur est obligatoire')]
    #[Groups(['etp:read'])]
    private ?string $educator = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['etp:read'])]
    private ?string $objectives = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['etp:read'])]
    private ?string $observations = null;

    #[ORM\ManyToOne(targetEntity: PatientProgressRef::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['etp:read'])]
    private ?PatientProgressRef $patientProgress = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['etp:read'])]
    private ?\DateTimeInterface $nextSessionDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['etp:read'])]
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

    public function getSessionDate(): ?\DateTimeInterface
    {
        return $this->sessionDate;
    }

    public function setSessionDate(?\DateTimeInterface $sessionDate): static
    {
        $this->sessionDate = $sessionDate;

        return $this;
    }

    public function getTopic(): ?EducationTopic
    {
        return $this->topic;
    }

    public function setTopic(?EducationTopic $topic): static
    {
        $this->topic = $topic;

        return $this;
    }

    public function getEducator(): ?string
    {
        return $this->educator;
    }

    public function setEducator(string $educator): static
    {
        $this->educator = $educator;

        return $this;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function setObjectives(?string $objectives): static
    {
        $this->objectives = $objectives;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function getPatientProgress(): ?PatientProgressRef
    {
        return $this->patientProgress;
    }

    public function setPatientProgress(?PatientProgressRef $patientProgress): static
    {
        $this->patientProgress = $patientProgress;

        return $this;
    }

    public function getNextSessionDate(): ?\DateTimeInterface
    {
        return $this->nextSessionDate;
    }

    public function setNextSessionDate(?\DateTimeInterface $nextSessionDate): static
    {
        $this->nextSessionDate = $nextSessionDate;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
