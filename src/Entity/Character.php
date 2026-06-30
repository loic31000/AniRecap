<?php

namespace App\Entity;

use App\Enum\SpoilerLevel;
use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 20, enumType: SpoilerLevel::class)]
    private SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    /**
     * @var Collection<int, Anime>
     */
    #[ORM\ManyToMany(targetEntity: Anime::class, mappedBy: 'characters')]
    private Collection $animes;

    /**
     * @var Collection<int, Manga>
     */
    #[ORM\ManyToMany(targetEntity: Manga::class, mappedBy: 'character')]
    private Collection $mangas;

    /**
     * @var Collection<int, Season>
     */
    #[ORM\ManyToMany(targetEntity: Season::class, mappedBy: 'character')]
    private Collection $seasons;


    /**
     * @var Collection<int, Episode>
     */
    #[ORM\ManyToMany(targetEntity: Episode::class, mappedBy: 'character')]
    private Collection $episodes;

    /**
     * @var Collection<int, Tome>
     */
    #[ORM\ManyToMany(targetEntity: Tome::class, mappedBy: 'character')]
    private Collection $tomes;

    /**
     * @var Collection<int, Chapitre>
     */
    #[ORM\ManyToMany(targetEntity: Chapitre::class, mappedBy: 'character')]
    private Collection $chapitres;

    /**
     * @var Collection<int, SpoilerPreference>
     */
    #[ORM\OneToMany(targetEntity: SpoilerPreference::class, mappedBy: 'character')]
    private Collection $spoilerPreferences;

    public function __construct()
    {
        $this->animes = new ArrayCollection();
        $this->mangas = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->episodes = new ArrayCollection();
        $this->tomes = new ArrayCollection();
        $this->chapitres = new ArrayCollection();
        $this->spoilerPreferences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getSpoilerLevel(): SpoilerLevel
    {
        return $this->spoilerLevel;
    }

    public function setSpoilerLevel(SpoilerLevel $spoilerLevel): static
    {
        $this->spoilerLevel = $spoilerLevel;
        return $this;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->addCharacter($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            $season->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode): static
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->addCharacter($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): static
    {
        if ($this->episodes->removeElement($episode)) {
            $episode->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Tome>
     */
    public function getTomes(): Collection
    {
        return $this->tomes;
    }

    public function addTome(Tome $tome): static
    {
        if (!$this->tomes->contains($tome)) {
            $this->tomes->add($tome);
            $tome->addCharacter($this);
        }

        return $this;
    }

    public function removeTome(Tome $tome): static
    {
        if ($this->tomes->removeElement($tome)) {
            $tome->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Anime>
     */
    public function getAnimes(): Collection
    {
        return $this->animes;
    }

    public function addAnime(Anime $anime): static
    {
        if (!$this->animes->contains($anime)) {
            $this->animes->add($anime);
            $anime->addCharacter($this);
        }

        return $this;
    }

    public function removeAnime(Anime $anime): static
    {
        if ($this->animes->removeElement($anime)) {
            $anime->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Manga>
     */
    public function getMangas(): Collection
    {
        return $this->mangas;
    }

    public function addManga(Manga $manga): static
    {
        if (!$this->mangas->contains($manga)) {
            $this->mangas->add($manga);
            $manga->addCharacter($this);
        }

        return $this;
    }

    public function removeManga(Manga $manga): static
    {
        if ($this->mangas->removeElement($manga)) {
            $manga->removeCharacter($this);
        }

        return $this;
    }

    public function getChapitres(): Collection
    {
        return $this->chapitres;
    }

    public function addChapitre(Chapitre $chapitre): static
    {
        if (!$this->chapitres->contains($chapitre)) {
            $this->chapitres->add($chapitre);
            $chapitre->addCharacter($this);
        }

        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            $chapitre->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SpoilerPreference>
     */
    public function getSpoilerPreferences(): Collection
    {
        return $this->spoilerPreferences;
    }

    public function addSpoilerPreference(SpoilerPreference $spoilerPreference): static
    {
        if (!$this->spoilerPreferences->contains($spoilerPreference)) {
            $this->spoilerPreferences->add($spoilerPreference);
            $spoilerPreference->setCharacter($this);
        }

        return $this;
    }

    public function removeSpoilerPreference(SpoilerPreference $spoilerPreference): static
    {
        if ($this->spoilerPreferences->removeElement($spoilerPreference)) {
            // set the owning side to null (unless already changed)
            if ($spoilerPreference->getCharacter() === $this) {
                $spoilerPreference->setCharacter(null);
            }
        }

        return $this;
    }
}
