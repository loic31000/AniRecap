<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class OeuvreCard
{
    public array $results = [];

    public bool $prioritizeFirst = false;
}
