<?php

namespace App\Dto;

use App\Entity\Categorie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateRanges')]
final class MangaSynopsisInput
{
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
        extensionsMessage: 'L’extension doit être .png, .jpg ou .jpeg et correspondre au contenu du fichier.',
        maxWidthMessage: 'La largeur de la miniature ne doit pas dépasser 4096 pixels.',
        maxHeightMessage: 'La hauteur de la miniature ne doit pas dépasser 4096 pixels.',
        maxPixelsMessage: 'La miniature contient trop de pixels (maximum : 16 777 216).',
        corruptedMessage: 'La miniature est corrompue ou n’est pas une image valide.',
    )]
    public ?UploadedFile $image = null;

    #[Assert\NotBlank(message: 'Saisissez le titre du manga.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le titre doit contenir au moins 2 caractères.',
        maxMessage: 'Le titre ne doit pas dépasser 255 caractères.',
    )]
    public ?string $title = null;

    /**
     * @var Categorie[]
     */
    #[Assert\Count(min: 1, max: 5, minMessage: 'Sélectionnez au moins une catégorie.', maxMessage: 'Sélectionnez au maximum cinq catégories.')]
    public array $categories = [];

    #[Assert\NotBlank(message: 'Saisissez le synopsis.')]
    #[Assert\Length(
        min: 20,
        max: 20_000,
        minMessage: 'Le synopsis doit contenir au moins 20 caractères.',
        maxMessage: 'Le synopsis ne doit pas dépasser 20 000 caractères.',
    )]
    public ?string $synopsis = null;

    #[Assert\NotBlank(message: 'Choisissez un statut.')]
    #[Assert\Choice(choices: ['En cours', 'Terminé'], message: 'Choisissez le statut « En cours » ou « Terminé ».')]
    public ?string $status = null;

    #[Assert\NotBlank(message: 'Saisissez le nom de l’auteur.')]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'Le nom de l’auteur doit contenir au moins 2 caractères.',
        maxMessage: 'Le nom de l’auteur ne doit pas dépasser 150 caractères.',
    )]
    public ?string $author = null;

    #[Assert\NotNull(message: 'Choisissez une date de sortie.')]
    #[Assert\Range(
        min: '1900-01-01',
        max: '2100-12-31',
        notInRangeMessage: 'La date doit être comprise entre le 01/01/1900 et le 31/12/2100.',
    )]
    public ?\DateTimeImmutable $releaseDate = null;

    #[Assert\NotNull(message: 'Indiquez le premier tome.')]
    #[Assert\Range(min: 1, max: 100_000, notInRangeMessage: 'Le premier tome doit être compris entre 1 et 100 000.')]
    public ?int $tomeStart = null;

    #[Assert\Range(min: 1, max: 100_000, notInRangeMessage: 'Le dernier tome doit être compris entre 1 et 100 000.')]
    public ?int $tomeEnd = null;

    #[Assert\NotNull(message: 'Indiquez le premier chapitre.')]
    #[Assert\Range(min: 1, max: 1_000_000, notInRangeMessage: 'Le premier chapitre doit être compris entre 1 et 1 000 000.')]
    public ?int $chapterStart = null;

    #[Assert\Range(min: 1, max: 1_000_000, notInRangeMessage: 'Le dernier chapitre doit être compris entre 1 et 1 000 000.')]
    public ?int $chapterEnd = null;

    public function validateRanges(ExecutionContextInterface $context): void
    {
        if ($this->tomeStart !== null && $this->tomeEnd !== null && $this->tomeEnd < $this->tomeStart) {
            $context->buildViolation('Le tome de fin doit être supérieur ou égal au tome de début.')
                ->atPath('tomeEnd')
                ->addViolation();
        }

        if ($this->chapterStart !== null && $this->chapterEnd !== null && $this->chapterEnd < $this->chapterStart) {
            $context->buildViolation('Le chapitre de fin doit être supérieur ou égal au chapitre de début.')
                ->atPath('chapterEnd')
                ->addViolation();
        }
    }
}
