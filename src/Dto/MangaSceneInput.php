<?php

namespace App\Dto;

use App\Enum\SpoilerLevel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class MangaSceneInput
{
    #[Assert\NotBlank(message: 'Choisissez le type de cible.')]
    #[Assert\Choice(choices: ['tome', 'chapitre'], message: 'Choisissez Tome ou Chapitre.')]
    public ?string $targetType = null;

    #[Assert\Positive(message: 'Choisissez un tome valide.')]
    public ?int $tomeId = null;

    #[Assert\Positive(message: 'Choisissez un chapitre valide.')]
    public ?int $chapitreId = null;

    #[Assert\NotBlank(message: 'Saisissez le titre de la scène.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Décrivez la scène.')]
    #[Assert\Length(min: 20, max: 20_000)]
    public ?string $content = null;

    #[Assert\NotNull(message: 'Choisissez un niveau de spoiler.')]
    public ?SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;

    #[Assert\NotNull(message: 'Veuillez sélectionner une image de scène.')]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/png', 'image/jpeg'],
        extensions: ['png', 'jpg', 'jpeg'],
        maxWidth: 4096,
        maxHeight: 4096,
        maxPixels: 16_777_216,
        detectCorrupted: true,
        maxSizeMessage: 'L’image ne doit pas dépasser 2 Mio.',
        mimeTypesMessage: 'L’image doit être une véritable image PNG ou JPEG.',
        extensionsMessage: 'L’extension doit être .png, .jpg ou .jpeg.',
        corruptedMessage: 'L’image est corrompue ou invalide.',
    )]
    public ?UploadedFile $image = null;
}
