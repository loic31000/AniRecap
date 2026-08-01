# Audit technique d’accessibilité AniRecap

- Date : 1 août 2026
- Référentiel : RGAA 4.1.2 / WCAG 2.1 niveaux A et AA
- Nature : auto-évaluation technique préparatoire
- Branche : `65-accessibilite-dwwm`
- État déclaré : non conforme tant que la grille complète des 106 critères n’est pas finalisée

## Échantillon contrôlé

Pages publiques :

- `/home` ;
- `/login` ;
- `/register` ;
- `/accessibilite` ;
- `/politique-de-confidentialite`.

Pages authentifiées :

- `/catalogue` ;
- `/profile` ;
- `/favoris` ;
- `/mes-resumes` ;
- `/formulaires` ;
- les formulaires Synopsis Anime, Synopsis Manga, Saison, Scène Saison, Scène Manga, Personnage et Diaporama ;
- une fiche Anime et une fiche Manga ;
- l’état invalide du formulaire d’inscription.

L’échantillon couvre les gabarits publics, l’authentification, la navigation principale, les listes, les fiches, les formulaires, les fenêtres modales et les états d’erreur. Les pages dépendant d’identifiants privés absents des données de développement n’ont pas toutes pu être exécutées individuellement.

## Outils et environnement

- axe-core CLI 4.12.1 ;
- Google Chrome 150 et ChromeDriver 150 ;
- inspection Twig, HTML, CSS et Stimulus ;
- navigation clavier automatisée avec Selenium ;
- calcul des contrastes selon la luminance relative WCAG ;
- PHPUnit, lints Symfony et validation Doctrine.

La barre d’outils Symfony de l’environnement `dev` a été exclue des résultats : elle génère ses propres erreurs de contraste et de région, mais ne fait pas partie de l’application livrée en production.

## Résultats automatisés

- 5 pages publiques : aucune violation axe-core propre à AniRecap ;
- 12 pages authentifiées : aucune violation propre à AniRecap ;
- fiches Anime et Manga : aucune violation propre à AniRecap ;
- inscription invalide : aucune violation propre à AniRecap ;
- aucun lien vide `href="#"` détecté ;
- toutes les images Twig contrôlées possèdent une alternative ou sont décoratives ;
- 42 templates Twig valides ;
- 9 tests PHPUnit et 23 assertions réussis.

Un résultat axe-core sans violation ne constitue pas à lui seul une conformité RGAA.

## Contrôles manuels et semi-automatisés réalisés

| Thématique | Résultat technique | Preuves principales |
|---|---|---|
| Images | Validé sur l’échantillon | Alternatives des couvertures, miniatures, avatars et portraits ; images décoratives avec alternative vide. |
| Couleurs | Validé sur l’échantillon | Information doublée par texte/icône/état ARIA ; contraste doré/bleu 4,85:1 ; doré/fond sombre 10,47:1 ; texte principal 18,02:1. |
| Liens | Validé sur l’échantillon | Intitulés explicites, liens image nommés et absence de liens fictifs. |
| Scripts | Validé sur les composants testés | Onglets, spoilers, menu Création, calendrier, menus de gestion et lecteur utilisables au clavier. |
| Éléments obligatoires | Validé sur l’échantillon | Doctype, titres de page, `lang="fr"`, changements de langue japonaise et contenu principal. |
| Structure | Validé sur l’échantillon | Régions principales, titres structurés, listes et groupes de navigation. |
| Présentation | Partiellement validé | Focus visible, réduction des animations, largeurs mobiles sans débordement, police système et texte agrandissable. Le zoom navigateur graphique à 400 % reste à confirmer manuellement. |
| Formulaires | Validé sur les états testés | Libellés, aides, erreurs, CSRF, autocomplete des données d’identité et consentement explicite non précoché. |
| Navigation | Validé sur les parcours testés | Lien d’évitement premier au focus, navigation cohérente, focus contenu, fermeture Échap et restitution du focus. |
| Consultation | Validé sur l’échantillon | Pas de rafraîchissement automatique, animations réduites et contenus consultables en portrait mobile. |
| Cadres, tableaux et médias temporels | Non applicables | Aucun iframe, tableau de données, audio ou vidéo dans les gabarits audités. |

## Tests clavier enregistrés

- le premier `Tab` place le focus sur « Aller au contenu principal » ;
- l’ouverture du menu Création place le focus sur son bouton Fermer ;
- le focus reste dans la fenêtre modale ;
- `Échap` ferme le menu ;
- le focus revient au bouton ayant ouvert le menu ;
- le changement d’avatar est déclenchable par un vrai bouton et son état est annoncé avec `aria-live`.

## Non-conformités ou vérifications restantes

Ces points empêchent une déclaration officielle « partiellement conforme » ou « totalement conforme » à ce stade :

1. exécuter la grille officielle des 106 critères RGAA, critère par critère, et calculer le taux sur les seuls critères applicables ;
2. tester avec NVDA + Firefox et VoiceOver + Safari ;
3. confirmer manuellement le zoom texte 200 % et le zoom graphique 400 % sur chaque gabarit ;
4. tester les pages privées Saison, Episode, Tome, Chapitre, Personnage et Diaporama avec un jeu de données couvrant tous les états ;
5. tester les messages d’erreur de chaque formulaire après une soumission invalide ;
6. renseigner l’identité et le moyen de contact du responsable dans les pages Accessibilité et Confidentialité.

## Conclusion

Le socle technique audité ne présente aucune violation automatisée WCAG A/AA propre à AniRecap sur l’échantillon testé. La navigation clavier, les contrastes principaux, les formulaires et les composants interactifs représentatifs ont été contrôlés et corrigés.

Le statut reste néanmoins « Accessibilité : non conforme » tant que les contrôles manuels restants et la grille officielle ne permettent pas de calculer un taux fiable. Cette formulation évite de présenter au jury ou au public une déclaration de conformité non démontrée.

## Références

- https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/
- https://accessibilite.numerique.gouv.fr/ressources/kit-audit/
- https://accessibilite.numerique.gouv.fr/obligations/evaluation-conformite/
