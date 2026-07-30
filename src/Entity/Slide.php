<?php

namespace App\Entity;

use App\Enum\SpoilerLevel;
use App\Repository\SlideRepository;
use App\Security\Ownable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SlideRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SLIDE_DIAPORAMA_POSITION', columns: ['diaporama_id', 'position'])]
class Slide implements Ownable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'slides')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Diaporama $diaporama = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(length: 20, enumType: SpoilerLevel::class)]
    private SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    #[ORM\Column(nullable: true)]
    private ?int $startTimecodeSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?int $endTimecodeSeconds = null;

    #[ORM\ManyToOne(inversedBy: 'slides')]
    private ?Episode $episode = null;

    #[ORM\ManyToOne(inversedBy: 'slides')]
    private ?Tome $tome = null;

    #[ORM\ManyToOne(inversedBy: 'slides')]
    private ?Chapitre $chapitre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiaporama(): ?Diaporama
    {
        return $this->diaporama;
    }

    public function setDiaporama(?Diaporama $diaporama): static
    {
        $this->diaporama = $diaporama;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->diaporama?->getOwner();
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

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    public function getStartTimecodeSeconds(): ?int
    {
        return $this->startTimecodeSeconds;
    }

    public function setStartTimecodeSeconds(?int $startTimecodeSeconds): static
    {
        $this->startTimecodeSeconds = $startTimecodeSeconds;

        return $this;
    }

    public function getEndTimecodeSeconds(): ?int
    {
        return $this->endTimecodeSeconds;
    }

    public function setEndTimecodeSeconds(?int $endTimecodeSeconds): static
    {
        $this->endTimecodeSeconds = $endTimecodeSeconds;

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
}
