<?php

namespace App\Entity;

use App\Repository\OjtAssignmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OjtAssignmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OjtAssignment
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_COMPLETED = 'COMPLETED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $supervisor = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $companyName = null;

    #[ORM\Column(type: 'integer')]
    private int $requiredHours = 0;

    #[ORM\Column(type: 'float')]
    private float $hoursCompleted = 0;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, DailyLog> */
    #[ORM\OneToMany(targetEntity: DailyLog::class, mappedBy: 'assignment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dailyLogs;

    #[ORM\OneToOne(targetEntity: Certificate::class, mappedBy: 'assignment', cascade: ['persist', 'remove'])]
    private ?Certificate $certificate = null;

    public function __construct()
    {
        $this->dailyLogs = new ArrayCollection();
    }

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

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getSupervisor(): ?User
    {
        return $this->supervisor;
    }

    public function setSupervisor(?User $supervisor): static
    {
        $this->supervisor = $supervisor;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getRequiredHours(): int
    {
        return $this->requiredHours;
    }

    public function setRequiredHours(int $requiredHours): static
    {
        $this->requiredHours = $requiredHours;
        return $this;
    }

    public function getHoursCompleted(): float
    {
        return $this->hoursCompleted;
    }

    public function setHoursCompleted(float $hoursCompleted): static
    {
        $this->hoursCompleted = $hoursCompleted;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return Collection<int, DailyLog> */
    public function getDailyLogs(): Collection
    {
        return $this->dailyLogs;
    }

    public function addDailyLog(DailyLog $dailyLog): static
    {
        if (!$this->dailyLogs->contains($dailyLog)) {
            $this->dailyLogs->add($dailyLog);
            $dailyLog->setAssignment($this);
        }
        return $this;
    }

    public function removeDailyLog(DailyLog $dailyLog): static
    {
        if ($this->dailyLogs->removeElement($dailyLog)) {
            if ($dailyLog->getAssignment() === $this) {
                $dailyLog->setAssignment(null);
            }
        }
        return $this;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(?Certificate $certificate): static
    {
        // Maintain the owning side
        if ($certificate !== null && $certificate->getAssignment() !== $this) {
            $certificate->setAssignment($this);
        }
        $this->certificate = $certificate;
        return $this;
    }

    public function getCompletionPercentage(): float
    {
        if ($this->requiredHours <= 0) {
            return 0;
        }
        return min(100, round(($this->hoursCompleted / $this->requiredHours) * 100, 2));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student' => $this->student?->toArray(),
            'supervisor' => $this->supervisor?->toArray(),
            'companyName' => $this->companyName,
            'requiredHours' => $this->requiredHours,
            'hoursCompleted' => $this->hoursCompleted,
            'completionPercentage' => $this->getCompletionPercentage(),
            'startDate' => $this->startDate?->format('Y-m-d'),
            'endDate' => $this->endDate?->format('Y-m-d'),
            'status' => $this->status,
            'hasCertificate' => $this->certificate !== null,
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}
