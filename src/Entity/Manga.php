<?php

namespace App\Entity;

use App\Repository\MangaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MangaRepository::class)]
class Manga
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
    private ?string $coverMangaUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $mangaDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $author = null;

    /**
     * @var Collection<int, Tome>
     */
    #[ORM\OneToMany(targetEntity: Tome::class, mappedBy: 'manga', orphanRemoval: true)]
    private Collection $tomes;

    /**
     * @var Collection<int, Chapitre>
     */
    #[ORM\OneToMany(targetEntity: Chapitre::class, mappedBy: 'manga', orphanRemoval: true)]
    private Collection $chapitres;

    /**
     * @var Collection<int, Summary>
     */
    #[ORM\OneToMany(targetEntity: Summary::class, mappedBy: 'manga')]
    private Collection $summaries;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'manga')]
    private Collection $favorites;

    /**
     * @var Collection<int, Vote>
     */
    #[ORM\OneToMany(targetEntity: Vote::class, mappedBy: 'manga')]
    private Collection $votes;

    /**
     * @var Collection<int, Recommandation>
     */
    #[ORM\OneToMany(targetEntity: Recommandation::class, mappedBy: 'manga')]
    private Collection $recommandations;

    /**
     * @var Collection<int, Categorie>
     */
    #[ORM\ManyToMany(targetEntity: Categorie::class, inversedBy: 'mangas')]
    private Collection $categorie;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'mangas')]
    private Collection $character;

    public function __construct()
    {
        $this->tomes = new ArrayCollection();
        $this->chapitres = new ArrayCollection();
        $this->summaries = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->recommandations = new ArrayCollection();
        $this->categorie = new ArrayCollection();
        $this->character = new ArrayCollection();
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

    public function getCoverMangaUrl(): ?string
    {
        return $this->coverMangaUrl;
    }

    public function setCoverMangaUrl(?string $coverMangaUrl): static
    {
        $this->coverMangaUrl = $coverMangaUrl;

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

    public function getMangaDate(): ?int
    {
        return $this->mangaDate;
    }

    public function setMangaDate(?int $mangaDate): static
    {
        $this->mangaDate = $mangaDate;

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
            $tome->setManga($this);
        }

        return $this;
    }

    public function removeTome(Tome $tome): static
    {
        if ($this->tomes->removeElement($tome)) {
            // set the owning side to null (unless already changed)
            if ($tome->getManga() === $this) {
                $tome->setManga(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Chapitre>
     */
    public function getChapitres(): Collection
    {
        return $this->chapitres;
    }

    public function addChapitre(Chapitre $chapitre): static
    {
        if (!$this->chapitres->contains($chapitre)) {
            $this->chapitres->add($chapitre);
            $chapitre->setManga($this);
        }

        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            // set the owning side to null (unless already changed)
            if ($chapitre->getManga() === $this) {
                $chapitre->setManga(null);
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
            $summary->setManga($this);
        }

        return $this;
    }

    public function removeSummary(Summary $summary): static
    {
        if ($this->summaries->removeElement($summary)) {
            // set the owning side to null (unless already changed)
            if ($summary->getManga() === $this) {
                $summary->setManga(null);
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
            $favorite->setManga($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getManga() === $this) {
                $favorite->setManga(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Vote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): static
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setManga($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): static
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getManga() === $this) {
                $vote->setManga(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Recommandation>
     */
    public function getRecommandations(): Collection
    {
        return $this->recommandations;
    }

    public function addRecommandation(Recommandation $recommandation): static
    {
        if (!$this->recommandations->contains($recommandation)) {
            $this->recommandations->add($recommandation);
            $recommandation->setManga($this);
        }

        return $this;
    }

    public function removeRecommandation(Recommandation $recommandation): static
    {
        if ($this->recommandations->removeElement($recommandation)) {
            // set the owning side to null (unless already changed)
            if ($recommandation->getManga() === $this) {
                $recommandation->setManga(null);
            }
        }

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
            $categorie->addManga($this);
        }

        return $this;
    }

    public function removeCategorie(Categorie $categorie): static
    {
        if ($this->categorie->removeElement($categorie)) {
            $categorie->removeManga($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->character;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->character->contains($character)) {
            $this->character->add($character);
            $character->addManga($this);
        }

        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->character->removeElement($character)) {
            $character->removeManga($this);
        }

        return $this;
    }
}
