<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SearchCard
{
    use DefaultActionTrait;

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
