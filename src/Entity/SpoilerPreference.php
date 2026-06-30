<?php

namespace App\Entity;

use App\Repository\SpoilerPreferenceRepository;
use App\Enum\SpoilerLevel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpoilerPreferenceRepository::class)]
class SpoilerPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $hideSpoiler = true;

    #[ORM\ManyToOne(inversedBy: 'spoilerPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'spoilerPreferences')]
    private ?Episode $episode = null;

    #[ORM\ManyToOne(inversedBy: 'spoilerPreferences')]
    private ?Tome $tome = null;

    #[ORM\ManyToOne(inversedBy: 'spoilerPreferences')]
    private ?Chapitre $chapitre = null;

    #[ORM\ManyToOne(inversedBy: 'spoilerPreferences')]
    private ?Character $character = null;


    #[ORM\Column(length: 20, enumType: SpoilerLevel::class)]
    private SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isHideSpoiler(): bool
    {
        return $this->hideSpoiler;
    }

    public function setHideSpoiler(bool $hideSpoiler): static
    {
        $this->hideSpoiler = $hideSpoiler;

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

    public function getEpisode(): ?Episode
    {
        return $this->episode;
    }

    public function setEpisode(?Episode $episode): static
    {
        $this->episode = $episode;

        return $this;
    }

    public function getTome(): ?Tome
    {
        return $this->tome;
    }

    public function setTome(?Tome $tome): static
    {
        $this->tome = $tome;

        return $this;
    }

    public function getChapitre(): ?Chapitre
    {
        return $this->chapitre;
    }

    public function setChapitre(?Chapitre $chapitre): static
    {
        $this->chapitre = $chapitre;

        return $this;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(?Character $character): static
    {
        $this->character = $character;

        return $this;
    }

    public function getSpoilerLevel(): SpoilerLevel
    {
        return $this->spoilerLevel;
    }

    public function setSpoilerLevel(SpoilerLevel $spoilerLevel): self
    {
        $this->spoilerLevel = $spoilerLevel;

        return $this;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'SpoilerLevel') {
            return $this->spoilerLevel;
        }

        trigger_error('Undefined property: ' . static::class . '::$' . $name, E_USER_NOTICE);
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'SpoilerLevel') {
            $this->spoilerLevel = $value;
            return;
        }

        $this->$name = $value;
    }
}