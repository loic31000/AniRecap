<?php

namespace App\Entity;

use App\Repository\DiaporamaRepository;
use App\Security\Ownable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiaporamaRepository::class)]
class Diaporama implements Ownable
{
    public const SOURCE_ANIME = 'anime';
    public const SOURCE_MANGA = 'manga';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'diaporamas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 10)]
    private ?string $sourceType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageFilename = null;

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\OneToMany(targetEntity: Slide::class, mappedBy: 'diaporama', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $slides;

    /**
     * @var Collection<int, Categorie>
     */
    #[ORM\ManyToMany(targetEntity: Categorie::class, inversedBy: 'diaporamas')]
    private Collection $categorie;

    public function __construct()
    {
        $this->categorie = new ArrayCollection();
        $this->slides = new ArrayCollection();
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getOwner(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): static
    {
        if (!in_array($sourceType, [self::SOURCE_ANIME, self::SOURCE_MANGA], true)) {
            throw new \InvalidArgumentException('Le type de source du diaporama est invalide.');
        }

        $this->sourceType = $sourceType;

        return $this;
    }

    public function getCoverImageFilename(): ?string
    {
        return $this->coverImageFilename;
    }

    public function setCoverImageFilename(?string $coverImageFilename): static
    {
        $this->coverImageFilename = $coverImageFilename;

        return $this;
    }

    /**
     * @return Collection<int, Slide>
     */
    public function getSlides(): Collection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): static
    {
        if (!$this->slides->contains($slide)) {
            $this->slides->add($slide);
            $slide->setDiaporama($this);
        }

        return $this;
    }

    public function removeSlide(Slide $slide): static
    {
        if ($this->slides->removeElement($slide) && $slide->getDiaporama() === $this) {
            $slide->setDiaporama(null);
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
            $categorie->addDiaporama($this);
        }

        return $this;
    }

    public function removeCategorie(Categorie $categorie): static
    {
        if ($this->categorie->removeElement($categorie)) {
            $categorie->removeDiaporama($this);
        }

        return $this;
    }
}
