<?php

namespace App\Form;

use App\Dto\SeasonInput;
use App\Entity\Anime;
use App\Entity\Categorie;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anime', EntityType::class, [
                'class' => Anime::class,
                'choice_label' => 'title',
                'query_builder' => fn (AnimeRepository $repository) => $repository->createOwnedPrivateQueryBuilder($options['owner']),
                'label' => 'Animé parent',
                'placeholder' => 'Choisissez un animé privé',
                'disabled' => $options['is_edit'],
                'invalid_message' => 'Cet animé n’est pas disponible dans votre espace.',
            ])
            ->add('image', FileType::class, [
                'label' => 'Miniature de la saison',
                'required' => !$options['is_edit'],
                'help' => $options['is_edit']
                    ? 'Laissez vide pour conserver la miniature actuelle. PNG ou JPG, 2 Mio maximum.'
                    : 'PNG ou JPG, 2 Mio maximum. Format 9:16 recommandé.',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de la saison',
            ])
            ->add('number', IntegerType::class, [
                'label' => 'Numéro de saison',
                'attr' => ['min' => 1, 'max' => 999],
                'invalid_message' => 'Le numéro de saison doit être un nombre entier.',
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'label' => 'Catégories',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => static fn (CategorieRepository $repository) => $repository->createAlphabeticalQueryBuilder(),
                'help' => 'Choisissez entre une et cinq catégories.',
                'invalid_message' => 'Une catégorie sélectionnée n’existe pas.',
                'choice_attr' => static fn () => [
                    'data-category-selector-target' => 'checkbox',
                    'data-action' => 'change->category-selector#change',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de la saison',
                'attr' => ['rows' => 10],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => ['Animé' => 'Anime'],
                'expanded' => true,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['En cours' => 'En cours', 'Terminé' => 'Terminé'],
                'expanded' => true,
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
            ])
            ->add('releaseYear', IntegerType::class, [
                'label' => 'Date de sortie',
                'attr' => ['min' => 1900, 'max' => 2100, 'placeholder' => '2026'],
                'invalid_message' => 'La date de sortie doit être une année valide.',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Sauvegarder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeasonInput::class,
            'is_edit' => false,
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
        $resolver->setRequired('owner');
        $resolver->setAllowedTypes('owner', User::class);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
