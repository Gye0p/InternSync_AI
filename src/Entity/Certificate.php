<?php

namespace App\Entity;

use App\Repository\CertificateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CertificateRepository::class)]
class Certificate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: OjtAssignment::class, inversedBy: 'certificate')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OjtAssignment $assignment = null;

    #[ORM\Column(type: 'string', length: 500)]
    private ?string $filePath = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $generatedAt = null;

    public function __construct()
    {
        $this->generatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssignment(): ?OjtAssignment
    {
        return $this->assignment;
    }

    public function setAssignment(OjtAssignment $assignment): static
    {
        $this->assignment = $assignment;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignmentId' => $this->assignment?->getId(),
            'filePath' => $this->filePath,
            'generatedAt' => $this->generatedAt?->format('c'),
        ];
    }
}
