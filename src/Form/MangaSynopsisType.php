<?php

namespace App\Form;

use App\Dto\MangaSynopsisInput;
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

final class MangaSynopsisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Miniature du synopsis',
                'help' => 'PNG ou JPG, 2 Mio maximum. Format 9:16 recommandé.',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre du manga',
                'attr' => ['placeholder' => 'Ex. Dragon Ball Z'],
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
                'data' => 'Manga',
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
            ->add('tomeStart', IntegerType::class, [
                'label' => 'Premier tome',
                'attr' => ['min' => 1, 'max' => 100000],
                'invalid_message' => 'Le premier tome doit être un nombre entier.',
            ])
            ->add('tomeEnd', IntegerType::class, [
                'label' => 'Dernier tome (facultatif)',
                'required' => false,
                'attr' => ['min' => 1, 'max' => 100000],
                'invalid_message' => 'Le dernier tome doit être un nombre entier.',
            ])
            ->add('chapterStart', IntegerType::class, [
                'label' => 'Premier chapitre',
                'attr' => ['min' => 1, 'max' => 1000000],
                'invalid_message' => 'Le premier chapitre doit être un nombre entier.',
            ])
            ->add('chapterEnd', IntegerType::class, [
                'label' => 'Dernier chapitre (facultatif)',
                'required' => false,
                'attr' => ['min' => 1, 'max' => 1000000],
                'invalid_message' => 'Le dernier chapitre doit être un nombre entier.',
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MangaSynopsisInput::class,
            'csrf_token_id' => 'manga_synopsis_create',
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
    }
}
