<?php

namespace App\Dto;

use App\Enum\SpoilerLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class SummaryEditInput
{
    #[Assert\NotBlank(message: 'Saisissez le titre du résumé.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Saisissez le contenu du résumé.')]
    #[Assert\Length(min: 20, max: 20_000)]
    public ?string $content = null;

    #[Assert\NotNull(message: 'Choisissez un niveau de spoiler.')]
    public ?SpoilerLevel $spoilerLevel = SpoilerLevel::Aucun;
}
