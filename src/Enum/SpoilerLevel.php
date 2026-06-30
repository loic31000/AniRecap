<?php
namespace App\Enum;
enum SpoilerLevel: string
{
    case Aucun  = 'aucun';
    case Mineur = 'mineur';
    case Majeur = 'majeur';
}