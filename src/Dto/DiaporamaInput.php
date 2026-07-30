<?php

namespace App\Dto;

use App\Entity\Categorie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class DiaporamaInput
{
    #[Assert\NotBlank(message: 'Saisissez le titre du diaporama.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 20_000)]
    public ?string $content = null;

    #[Assert\NotBlank(message: 'Choisissez le type de source.')]
    #[Assert\Choice(choices: ['anime', 'manga'], message: 'Choisissez la source Anime ou Manga.')]
    public ?string $sourceType = null;

    #[Assert\NotNull(message: 'Veuillez sélectionner une miniature.')]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/png', 'image/jpeg'],
        extensions: ['png', 'jpg', 'jpeg'],
        maxWidth: 4096,
        maxHeight: 4096,
        maxPixels: 16_777_216,
        detectCorrupted: true,
        maxSizeMessage: 'La miniature ne doit pas dépasser 2 Mio.',
        mimeTypesMessage: 'La miniature doit être une véritable image PNG ou JPEG.',
        extensionsMessage: 'L’extension doit être .png, .jpg ou .jpeg.',
        corruptedMessage: 'La miniature est corrompue ou invalide.',
    )]
    public ?UploadedFile $image = null;

    /**
     * @var Categorie[]
     */
    #[Assert\Count(max: 5, maxMessage: 'Sélectionnez au maximum cinq catégories.')]
    public array $categories = [];
}
