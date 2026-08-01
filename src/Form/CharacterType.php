<?php

namespace App\Form;

use App\Dto\CharacterInput;
use App\Entity\Anime;
use App\Entity\Chapitre;
use App\Entity\Episode;
use App\Entity\Manga;
use App\Entity\Season;
use App\Entity\Tome;
use App\Enum\SpoilerLevel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Image du personnage',
                'required' => false,
                'help' => $options['is_edit'] ? 'Laissez vide pour conserver l’image actuelle.' : 'PNG ou JPG, 2 Mio maximum. Format carré recommandé.',
            ])
            ->add('name', TextType::class, ['label' => 'Nom du personnage', 'attr' => ['placeholder' => 'Ex. Sangoku, Luffy, Guts…']])
            ->add('description', TextareaType::class, ['label' => 'Biographie - Description', 'attr' => ['rows' => 7, 'placeholder' => 'Décrivez le personnage…']])
            ->add('spoilerLevel', ChoiceType::class, [
                'label' => 'Niveau de Spoiler',
                'choices' => ['Aucun' => SpoilerLevel::Aucun, 'Mineur' => SpoilerLevel::Mineur, 'Majeur' => SpoilerLevel::Majeur],
                'choice_value' => static fn (?SpoilerLevel $level): string => $level?->value ?? '',
                'choice_attr' => static fn (SpoilerLevel $level): array => ['data-subtitle' => match ($level) {
                    SpoilerLevel::Aucun => 'Public', SpoilerLevel::Mineur => 'Événements', SpoilerLevel::Majeur => 'Révélations',
                }],
                'expanded' => true,
            ]);

        foreach ([
            'animes' => [Anime::class, 'Anime liés'],
            'seasons' => [Season::class, 'Saisons liées'],
            'episodes' => [Episode::class, 'Épisodes liés'],
            'mangas' => [Manga::class, 'Manga liés'],
            'tomes' => [Tome::class, 'Tomes liés'],
            'chapitres' => [Chapitre::class, 'Chapitres liés'],
        ] as $name => [$class, $label]) {
            $builder->add($name, EntityType::class, [
                'class' => $class,
                'choices' => $options['owned_choices'][$name],
                'choice_label' => static fn (object $entity): string => self::choiceLabel($entity),
                'label' => $label,
                'multiple' => true,
                'required' => false,
                'invalid_message' => 'Une cible sélectionnée n’est pas disponible dans votre espace.',
            ]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    private static function choiceLabel(object $entity): string
    {
        return match (true) {
            $entity instanceof Anime, $entity instanceof Manga => (string) $entity->getTitle(),
            $entity instanceof Season => $entity->getAnime()?->getTitle() . ' — Saison ' . $entity->getNumber() . ' — ' . $entity->getTitle(),
            $entity instanceof Episode => $entity->getSeason()?->getAnime()?->getTitle() . ' — S' . $entity->getSeason()?->getNumber() . ' E' . $entity->getNumber() . ' — ' . $entity->getTitle(),
            $entity instanceof Tome => $entity->getManga()?->getTitle() . ' — Tome ' . $entity->getNumber() . ' — ' . $entity->getTitle(),
            $entity instanceof Chapitre => $entity->getManga()?->getTitle() . ' — Chapitre ' . $entity->getNumber() . ' — ' . $entity->getTitle(),
            default => '',
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CharacterInput::class,
            'is_edit' => false,
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
        $resolver->setRequired('owned_choices');
        $resolver->setAllowedTypes('owned_choices', 'array');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
