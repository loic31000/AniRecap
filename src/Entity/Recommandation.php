<?php

namespace App\Entity;

use App\Repository\RecommandationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommandationRepository::class)]
class Recommandation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $lastUpdated = null;

    #[ORM\Column]
    private ?int $popularityScore = null;

    #[ORM\ManyToOne(inversedBy: 'recommandations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'recommandations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Anime $anime = null;

    #[ORM\ManyToOne(inversedBy: 'recommandations')]
    private ?Manga $manga = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastUpdated(): ?\DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTime $lastUpdated): static
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getPopularityScore(): ?int
    {
        return $this->popularityScore;
    }

    public function setPopularityScore(int $popularityScore): static
    {
        $this->popularityScore = $popularityScore;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAnime(): ?Anime
    {
        return $this->anime;
    }

    public function setAnime(?Anime $anime): static
    {
        $this->anime = $anime;

        return $this;
    }

    public function getManga(): ?Manga
    {
        return $this->manga;
    }

    public function setManga(?Manga $manga): static
    {
        $this->manga = $manga;

        return $this;
    }
}
