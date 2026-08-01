<?php

namespace App\Dto;

use App\Entity\Categorie;
use App\Enum\SpoilerLevel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class TomeInput
{
    #[Assert\NotNull(message: 'Veuillez sélectionner une miniature.', groups: ['create'])]
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
        extensionsMessage: 'L’extension doit être .png, .jpg ou .jpeg et correspondre au contenu du fichier.',
        maxWidthMessage: 'La largeur ne doit pas dépasser 4096 pixels.',
        maxHeightMessage: 'La hauteur ne doit pas dépasser 4096 pixels.',
        maxPixelsMessage: 'La miniature contient trop de pixels (maximum : 16 777 216).',
        corruptedMessage: 'La miniature est corrompue ou n’est pas une image valide.',
    )]
    public ?UploadedFile $image = null;

    #[Assert\NotBlank(message: 'Saisissez le titre du tome.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le titre doit contenir au moins 2 caractères.', maxMessage: 'Le titre ne doit pas dépasser 255 caractères.')]
    public ?string $title = null;

    #[Assert\NotNull(message: 'Indiquez le numéro du tome.')]
    #[Assert\Range(min: 1, max: 10_000, notInRangeMessage: 'Le numéro doit être compris entre 1 et 10 000.')]
    public ?int $number = null;

    /** @var Categorie[] */
    #[Assert\Count(min: 1, max: 3, minMessage: 'Sélectionnez au moins une catégorie.', maxMessage: 'Sélectionnez au maximum trois catégories.')]
    public array $categories = [];

    #[Assert\NotBlank(message: 'Saisissez la description du tome.')]
    #[Assert\Length(min: 20, max: 20_000, minMessage: 'La description doit contenir au moins 20 caractères.', maxMessage: 'La description ne doit pas dépasser 20 000 caractères.')]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'Choisissez un type.')]
    #[Assert\Choice(choices: ['Manga'], message: 'Le type doit être « Manga ».')]
    public ?string $type = 'Manga';

    #[Assert\NotBlank(message: 'Choisissez un statut.')]
    #[Assert\Choice(choices: ['En cours', 'Terminé'], message: 'Choisissez le statut « En cours » ou « Terminé ».')]
    public ?string $status = null;

    #[Assert\NotBlank(message: 'Saisissez le nom de l’auteur.')]
    #[Assert\Length(min: 2, max: 150, minMessage: 'Le nom de l’auteur doit contenir au moins 2 caractères.', maxMessage: 'Le nom de l’auteur ne doit pas dépasser 150 caractères.')]
    public ?string $author = null;

    #[Assert\NotNull(message: 'Indiquez l’année de sortie.')]
    #[Assert\Range(min: 1900, max: 2100, notInRangeMessage: 'L’année doit être comprise entre 1900 et 2100.')]
    public ?int $releaseYear = null;

    #[Assert\NotNull(message: 'Choisissez un niveau de spoiler.')]
    public ?SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;
}
