<?php

namespace App\Entity;

use App\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_vote_user_anime', fields: ['user', 'anime'])]
#[ORM\UniqueConstraint(name: 'uq_vote_user_manga', fields: ['user', 'manga'])]
class Vote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'like')]
    private ?bool $like = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    private ?Anime $anime = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    private ?Manga $manga = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isLike(): ?bool
    {
        return $this->like;
    }

    public function setLike(bool $like): static
    {
        $this->like = $like;

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