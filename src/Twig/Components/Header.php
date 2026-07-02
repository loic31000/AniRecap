<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Header
{
    use DefaultActionTrait;

    public ?string $backRoute = null;
    public ?string $title = 'AniRecap';
}