<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de consultation est obligatoire')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du praticien est obligatoire')]
    private ?string $practitionerName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de consultation est obligatoire')]
    #[Assert\Choice(
        choices: ['Suivi post-greffe', 'Bilan pré-greffe', 'Urgence', 'Contrôle', 'Autre'],
        message: 'Type de consultation invalide'
    )]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Les observations sont obligatoires')]
    private ?string $observations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $treatmentNotes = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextAppointmentDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
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

    public function getPractitionerName(): ?string
    {
        return $this->practitionerName;
    }

    public function setPractitionerName(string $practitionerName): static
    {
        $this->practitionerName = $practitionerName;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
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
}
