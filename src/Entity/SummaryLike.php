<?php

namespace App\Entity;

use App\Repository\SummaryLikeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SummaryLikeRepository::class)]
#[ORM\Table(name: 'summary_like')]
#[ORM\UniqueConstraint(name: 'UNIQ_SUMMARY_LIKE_USER_SUMMARY', columns: ['user_id', 'summary_id'])]
#[ORM\Index(name: 'IDX_SUMMARY_LIKE_USER', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_SUMMARY_LIKE_SUMMARY', columns: ['summary_id'])]
class SummaryLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Summary $summary = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getSummary(): ?Summary { return $this->summary; }
    public function setSummary(Summary $summary): static { $this->summary = $summary; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
