<?php

namespace App\Form;

use App\Dto\AnimeSynopsisInput;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AnimeSynopsisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Miniature du synopsis',
                'help' => 'PNG ou JPG, 2 Mio maximum. Format 9:16 recommandé.',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de l’animé',
                'attr' => ['placeholder' => 'Ex. Arc Marineford'],
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'label' => 'Catégories',
                'multiple' => true,
                'placeholder' => 'Choisissez une ou plusieurs catégories',
                'invalid_message' => 'Une catégorie sélectionnée n’existe pas.',
            ])
            ->add('synopsis', TextareaType::class, [
                'label' => 'Synopsis',
                'attr' => ['rows' => 8, 'placeholder' => 'Décrivez le synopsis…'],
            ])
            ->add('sourceType', ChoiceType::class, [
                'label' => 'Type de source',
                'mapped' => false,
                'disabled' => true,
                'data' => 'Anime',
                'choices' => ['Animé' => 'Anime', 'Manga' => 'Manga'],
                'expanded' => true,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['En cours' => 'En cours', 'Terminé' => 'Terminé'],
                'expanded' => true,
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'attr' => ['placeholder' => 'Ex. Akira Toriyama'],
            ])
            ->add('releaseDate', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'Saisissez une date valide.',
            ])
            ->add('initialSeasonNumber', IntegerType::class, [
                'label' => 'Saison',
                'attr' => ['min' => 1, 'max' => 999],
                'invalid_message' => 'Le numéro de saison doit être un nombre entier.',
            ])
            ->add('episodeCount', IntegerType::class, [
                'label' => 'Nombre d’épisodes',
                'attr' => ['min' => 1, 'max' => 10000],
                'invalid_message' => 'Le nombre d’épisodes doit être un nombre entier.',
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnimeSynopsisInput::class,
            'csrf_token_id' => 'anime_synopsis_create',
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
    }
}
