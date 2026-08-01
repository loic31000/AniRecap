<?php

namespace App\Dto;

use App\Entity\Anime;
use App\Entity\Chapitre;
use App\Entity\Episode;
use App\Entity\Manga;
use App\Entity\Season;
use App\Entity\Tome;
use App\Enum\SpoilerLevel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class CharacterInput
{
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/png', 'image/jpeg'],
        extensions: ['png', 'jpg', 'jpeg'],
        maxWidth: 4096,
        maxHeight: 4096,
        maxPixels: 16_777_216,
        detectCorrupted: true,
        maxSizeMessage: 'L’image ne doit pas dépasser 2 Mio.',
        mimeTypesMessage: 'L’image doit être un véritable fichier PNG ou JPEG.',
        extensionsMessage: 'L’extension doit être .png, .jpg ou .jpeg et correspondre au fichier.',
        corruptedMessage: 'L’image est corrompue ou invalide.',
    )]
    public ?UploadedFile $image = null;

    #[Assert\NotBlank(message: 'Saisissez le nom du personnage.')]
    #[Assert\Length(min: 2, max: 150, minMessage: 'Le nom doit contenir au moins 2 caractères.', maxMessage: 'Le nom ne doit pas dépasser 150 caractères.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Saisissez la biographie du personnage.')]
    #[Assert\Length(min: 10, max: 20_000, minMessage: 'La biographie doit contenir au moins 10 caractères.', maxMessage: 'La biographie ne doit pas dépasser 20 000 caractères.')]
    public ?string $description = null;

    #[Assert\NotNull(message: 'Choisissez un niveau de spoiler.')]
    public SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    /** @var Anime[] */
    public array $animes = [];

    /** @var Season[] */
    public array $seasons = [];

    /** @var Episode[] */
    public array $episodes = [];

    /** @var Manga[] */
    public array $mangas = [];

    /** @var Tome[] */
    public array $tomes = [];

    /** @var Chapitre[] */
    public array $chapitres = [];
}
