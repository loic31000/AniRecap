<?php

namespace App\Twig\Components;

use App\Entity\Categorie;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SearchCard
{
    use DefaultActionTrait;

    /** @var Categorie[] */
    public array $genres = [];

    public array $filters = [
        'q' => '',
        'type' => 'all',
        'genre' => null,
        'annee' => null,
    ];
}
