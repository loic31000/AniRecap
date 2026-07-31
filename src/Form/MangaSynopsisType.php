<?php

namespace App\Form;

use App\Dto\MangaSynopsisInput;
use App\Entity\Categorie;
use App\Repository\CategorieRepository;
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
                'required' => !$options['is_edit'],
                'help' => $options['is_edit']
                    ? 'Laissez vide pour conserver la miniature actuelle.'
                    : 'PNG ou JPG, 2 Mio maximum. Format 9:16 recommandé.',
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
                'expanded' => true,
                'query_builder' => static fn (CategorieRepository $repository) => $repository->createAlphabeticalQueryBuilder(),
                'help' => 'Choisissez entre une et cinq catégories.',
                'invalid_message' => 'Une catégorie sélectionnée n’existe pas.',
                'row_attr' => ['class' => 'synopsis-category-field'],
                'choice_attr' => static fn () => [
                    'data-category-selector-target' => 'checkbox',
                    'data-action' => 'change->category-selector#change',
                ],
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
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
