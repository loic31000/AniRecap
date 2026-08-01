<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SearchCard
{
    /** @var list<array{slug: string, name: string}> */
    public array $genres = [];

    public array $filters = [
        'q' => '',
        'type' => 'all',
        'genre' => null,
        'annee' => null,
        'date' => null,
    ];

    public string $actionRoute = 'app_catalogue';
}
