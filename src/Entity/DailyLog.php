<?php

namespace App\Entity;

use App\Repository\DailyLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DailyLog
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OjtAssignment::class, inversedBy: 'dailyLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OjtAssignment $assignment = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $aiFeedback = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $skillTags = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $clarityScore = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $supervisorComment = null;

    #[ORM\Column(type: 'float')]
    private float $hoursWorked = 8;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssignment(): ?OjtAssignment
    {
        return $this->assignment;
    }

    public function setAssignment(?OjtAssignment $assignment): static
    {
        $this->assignment = $assignment;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getAiFeedback(): ?array
    {
        return $this->aiFeedback;
    }

    public function setAiFeedback(?array $aiFeedback): static
    {
        $this->aiFeedback = $aiFeedback;
        return $this;
    }

    public function getSkillTags(): ?array
    {
        return $this->skillTags;
    }

    public function setSkillTags(?array $skillTags): static
    {
        $this->skillTags = $skillTags;
        return $this;
    }

    public function getClarityScore(): ?int
    {
        return $this->clarityScore;
    }

    public function setClarityScore(?int $clarityScore): static
    {
        $this->clarityScore = $clarityScore;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSupervisorComment(): ?string
    {
        return $this->supervisorComment;
    }

    public function setSupervisorComment(?string $supervisorComment): static
    {
        $this->supervisorComment = $supervisorComment;
        return $this;
    }

    public function getHoursWorked(): float
    {
        return $this->hoursWorked;
    }

    public function setHoursWorked(float $hoursWorked): static
    {
        $this->hoursWorked = $hoursWorked;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignmentId' => $this->assignment?->getId(),
            'studentId' => $this->assignment?->getStudent()?->getId(),
            'studentName' => $this->assignment?->getStudent()?->getName(),
            'date' => $this->date?->format('Y-m-d'),
            'content' => $this->content,
            'aiFeedback' => $this->aiFeedback,
            'skillTags' => $this->skillTags,
            'clarityScore' => $this->clarityScore,
            'status' => $this->status,
            'supervisorComment' => $this->supervisorComment,
            'hoursWorked' => $this->hoursWorked,
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}
