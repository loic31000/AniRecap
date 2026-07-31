<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class DiaporamaEditInput
{
    #[Assert\NotBlank(message: 'Saisissez le titre du diaporama.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 20_000)]
    public ?string $content = null;
}
