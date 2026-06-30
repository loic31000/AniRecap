<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, Anime>
     */
    #[ORM\ManyToMany(targetEntity: Anime::class, mappedBy: 'categories')]
    private Collection $animes;

    /**
     * @var Collection<int, Manga>
     */
    #[ORM\ManyToMany(targetEntity: Manga::class, mappedBy: 'categorie')]
    private Collection $mangas;

    /**
     * @var Collection<int, Season>
     */
    #[ORM\ManyToMany(targetEntity: Season::class, mappedBy: 'categorie')]
    private Collection $seasons;

    /**
     * @var Collection<int, Episode>
     */
    #[ORM\ManyToMany(targetEntity: Episode::class, mappedBy: 'categorie')]
    private Collection $episodes;

    /**
     * @var Collection<int, Tome>
     */
    #[ORM\ManyToMany(targetEntity: Tome::class, mappedBy: 'categorie')]
    private Collection $tomes;

    /**
     * @var Collection<int, Chapitre>
     */
    #[ORM\ManyToMany(targetEntity: Chapitre::class, mappedBy: 'categorie')]
    private Collection $chapitres;

    /**
     * @var Collection<int, Diaporama>
     */
    #[ORM\ManyToMany(targetEntity: Diaporama::class, mappedBy: 'categorie')]
    private Collection $diaporamas;

    public function __construct()
    {
        $this->animes = new ArrayCollection();
        $this->mangas = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->episodes = new ArrayCollection();
        $this->tomes = new ArrayCollection();
        $this->chapitres = new ArrayCollection();
        $this->diaporamas = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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
            $season->addCategorie($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            $season->removeCategorie($this);
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
            $episode->addCategorie($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): static
    {
        if ($this->episodes->removeElement($episode)) {
            $episode->removeCategorie($this);
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
            $tome->addCategorie($this);
        }

        return $this;
    }

    public function removeTome(Tome $tome): static
    {
        if ($this->tomes->removeElement($tome)) {
            $tome->removeCategorie($this);
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
            $chapitre->addCategorie($this);
        }

        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            $chapitre->removeCategorie($this);
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
            $diaporama->addCategorie($this);
        }

        return $this;
    }

    public function removeDiaporama(Diaporama $diaporama): static
    {
        if ($this->diaporamas->removeElement($diaporama)) {
            $diaporama->removeCategorie($this);
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
            $anime->addCategorie($this);
        }

        return $this;
    }

    public function removeAnime(Anime $anime): static
    {
        if ($this->animes->removeElement($anime)) {
            $anime->removeCategorie($this);
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
            $manga->addCategorie($this);
        }

        return $this;
    }

    public function removeManga(Manga $manga): static
    {
        if ($this->mangas->removeElement($manga)) {
            $manga->removeCategorie($this);
        }

        return $this;
    }
}
