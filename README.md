# AniRecap

AniRecap est une application Symfony de consultation et de gestion privée de récapitulatifs d’animes et de mangas. Elle couvre l’authentification, le catalogue, les œuvres, saisons, épisodes, tomes, chapitres, personnages, favoris, résumés et diaporamas.

## Prérequis

- PHP 8.4 avec les extensions requises par Symfony et Doctrine
- Composer 2
- MySQL 8.0
- Symfony CLI, recommandé pour le serveur HTTPS local
- Docker Compose, facultatif pour MySQL et phpMyAdmin

## Installation

```bash
composer install
docker compose up -d database phpmyadmin
php bin/console doctrine:migrations:migrate
symfony serve
```

L’application est alors disponible via l’adresse indiquée par Symfony CLI. phpMyAdmin est exposé sur `http://localhost:8080` avec la configuration Docker par défaut.

La configuration locale et les secrets doivent rester dans `.env.local`, qui n’est pas versionné. Ne partagez jamais une `DATABASE_URL` contenant un mot de passe.

## Données de développement

Les fixtures réinitialisent entièrement la base ciblée. Elles ne doivent être chargées que sur une base de développement ou de test jetable :

```bash
php bin/console doctrine:fixtures:load
```

Le compte défini dans `AppFixtures` est réservé à la démonstration locale. Changez ses identifiants avant toute mise en ligne.

## Vérifications

```bash
php bin/phpunit
php bin/console doctrine:schema:validate
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console lint:yaml config/
git diff --check
```

Pour vérifier les routes :

```bash
php bin/console debug:router
```

## Architecture

- `src/Controller/` : contrôleurs HTTP Symfony
- `src/Entity/` : modèle Doctrine
- `src/Repository/` : requêtes filtrées et contrôles de visibilité
- `src/Form/` et `src/Dto/` : formulaires et données validées
- `src/Service/` : upload privé et opérations métier partagées
- `templates/` : vues Twig et composants partagés
- `assets/controllers/` : contrôleurs Stimulus
- `migrations/` : historique reproductible du schéma MySQL
- `tests/` : tests PHPUnit

## Sécurité et propriété

- Les créations personnelles sont privées par défaut.
- Le propriétaire est toujours déterminé côté serveur ; il ne vient jamais d’un champ de formulaire.
- Les repositories filtrés et les contrôles d’accès empêchent Alice de consulter les contenus privés de Bob.
- Les images privées sont stockées hors de `public/` et servies par des routes contrôlées.
- Les uploads acceptent uniquement de véritables images PNG ou JPEG validées côté serveur.
- Les fichiers de `public/uploads/avatars/` sont des données utilisateur et ne doivent jamais être commités.

## Base de données

Toute évolution du modèle passe par une migration Doctrine. Une migration destructive doit d’abord être testée sur une copie isolée de la base. Ne réécrivez pas une migration déjà partagée et ne lancez jamais une migration sans vérifier la base ciblée.

## Workflow Git

Le projet utilise une branche par user story ou correctif. Une branche ne doit contenir qu’un périmètre cohérent. Aucun merge, rebase, reset, commit ou push ne doit être effectué sans validation explicite.
