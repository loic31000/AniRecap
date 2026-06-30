<?php
namespace App\Entity;

use App\Enum\SpoilerLevel;
use App\Repository\EpisodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
class Episode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $coverEpisodeUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(nullable: true)]
    private ?int $episodeDate = null;

    #[ORM\Column(length: 20, enumType: SpoilerLevel::class)]
    private SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    #[ORM\ManyToOne(inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Season $season = null;

    #[ORM\ManyToOne(inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Categorie>
     */
    #[ORM\ManyToMany(targetEntity: Categorie::class, inversedBy: 'episodes')]
    private Collection $categorie;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'episodes')]
    private Collection $character;

    /**
     * @var Collection<int, Diaporama>
     */
    #[ORM\OneToMany(targetEntity: Diaporama::class, mappedBy: 'episode', orphanRemoval: true)]
    private Collection $diaporamas;

    /**
     * @var Collection<int, Summary>
     */
    #[ORM\OneToMany(targetEntity: Summary::class, mappedBy: 'episode')]
    private Collection $summaries;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'episode')]
    private Collection $favorites;

    /**
     * @var Collection<int, SpoilerPreference>
     */
    #[ORM\OneToMany(targetEntity: SpoilerPreference::class, mappedBy: 'episode')]
    private Collection $spoilerPreferences;

    public function __construct()
    {
        $this->categorie = new ArrayCollection();
        $this->character = new ArrayCollection();
        $this->diaporamas = new ArrayCollection();
        $this->summaries = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->spoilerPreferences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    public function setSynopsis(?string $synopsis): static
    {
        $this->synopsis = $synopsis;
        return $this;
    }

    public function getCoverEpisodeUrl(): ?string
    {
        return $this->coverEpisodeUrl;
    }

    public function setCoverEpisodeUrl(?string $coverEpisodeUrl): static
    {
        $this->coverEpisodeUrl = $coverEpisodeUrl;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getEpisodeDate(): ?int
    {
        return $this->episodeDate;
    }

    public function setEpisodeDate(?int $episodeDate): static
    {
        $this->episodeDate = $episodeDate;
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

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;
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

    /**
     * @return Collection<int, Categorie>
     */
    public function getCategorie(): Collection
    {
        return $this->categorie;
    }

    public function addCategorie(Categorie $categorie): static
    {
        if (!$this->categorie->contains($categorie)) {
            $this->categorie->add($categorie);
            $categorie->addEpisode($this);
        }
        return $this;
    }

    public function removeCategorie(Categorie $categorie): static
    {
        if ($this->categorie->removeElement($categorie)) {
            $categorie->removeEpisode($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacter(): Collection
    {
        return $this->character;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->character->contains($character)) {
            $this->character->add($character);
            $character->addEpisode($this);
        }
        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->character->removeElement($character)) {
            $character->removeEpisode($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Diaporama>
     */
    public function getDiaporamas(): Collection
    {
        return $this->diaporamas;
    }

    public function addDiaporama(Diaporama $diaporama): static
    {
        if (!$this->diaporamas->contains($diaporama)) {
            $this->diaporamas->add($diaporama);
            $diaporama->setEpisode($this);
        }

        return $this;
    }

    public function removeDiaporama(Diaporama $diaporama): static
    {
        if ($this->diaporamas->removeElement($diaporama)) {
            // set the owning side to null (unless already changed)
            if ($diaporama->getEpisode() === $this) {
                $diaporama->setEpisode(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Summary>
     */
    public function getSummaries(): Collection
    {
        return $this->summaries;
    }

    public function addSummary(Summary $summary): static
    {
        if (!$this->summaries->contains($summary)) {
            $this->summaries->add($summary);
            $summary->setEpisode($this);
        }

        return $this;
    }

    public function removeSummary(Summary $summary): static
    {
        if ($this->summaries->removeElement($summary)) {
            // set the owning side to null (unless already changed)
            if ($summary->getEpisode() === $this) {
                $summary->setEpisode(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setEpisode($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getEpisode() === $this) {
                $favorite->setEpisode(null);
            }
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
            $spoilerPreference->setEpisode($this);
        }

        return $this;
    }

    public function removeSpoilerPreference(SpoilerPreference $spoilerPreference): static
    {
        if ($this->spoilerPreferences->removeElement($spoilerPreference)) {
            // set the owning side to null (unless already changed)
            if ($spoilerPreference->getEpisode() === $this) {
                $spoilerPreference->setEpisode(null);
            }
        }

        return $this;
    }
}