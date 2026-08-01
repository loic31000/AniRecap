<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Header
{
    public ?string $backRoute = null;
    public ?string $title = 'AniRecap';
}
