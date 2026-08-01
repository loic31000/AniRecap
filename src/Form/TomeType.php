<?php

namespace App\Form;

use App\Dto\TomeInput;
use App\Entity\Categorie;
use App\Enum\SpoilerLevel;
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

final class TomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addFields($builder, $options['is_edit']);
    }

    private function addFields(FormBuilderInterface $builder, bool $isEdit): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Miniature du tome',
                'required' => !$isEdit,
                'help' => $isEdit ? 'Laissez vide pour conserver la miniature actuelle. PNG ou JPG, 2 Mio maximum.' : 'PNG ou JPG, 2 Mio maximum.',
            ])
            ->add('title', TextType::class, ['label' => 'Titre du tome'])
            ->add('number', IntegerType::class, ['label' => 'Numéro du tome', 'attr' => ['min' => 1, 'max' => 10000], 'invalid_message' => 'Le numéro doit être un nombre entier.'])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'label' => 'Catégories',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => static fn (CategorieRepository $repository) => $repository->createAlphabeticalQueryBuilder(),
                'help' => 'Choisissez entre une et trois catégories.',
                'invalid_message' => 'Une catégorie sélectionnée n’existe pas.',
                'choice_attr' => static fn () => [
                    'data-category-selector-target' => 'checkbox',
                    'data-action' => 'change->category-selector#change',
                ],
            ])
            ->add('description', TextareaType::class, ['label' => 'Description du tome', 'attr' => ['rows' => 10]])
            ->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => ['Manga' => 'Manga'], 'expanded' => true])
            ->add('status', ChoiceType::class, ['label' => 'Statut', 'choices' => ['En cours' => 'En cours', 'Terminé' => 'Terminé'], 'expanded' => true])
            ->add('author', TextType::class, ['label' => 'Auteur'])
            ->add('releaseYear', IntegerType::class, ['label' => 'Date de sortie', 'attr' => ['min' => 1900, 'max' => 2100], 'invalid_message' => 'La date de sortie doit être une année valide.'])
            ->add('spoilerLevel', ChoiceType::class, [
                'label' => 'Niveau de spoiler',
                'choices' => ['Aucun spoiler' => SpoilerLevel::Aucun, 'Spoiler mineur' => SpoilerLevel::Mineur, 'Spoiler majeur' => SpoilerLevel::Majeur],
                'choice_value' => static fn (?SpoilerLevel $level): string => $level?->value ?? '',
                'expanded' => true,
                'invalid_message' => 'Choisissez un niveau de spoiler valide.',
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TomeInput::class, 'is_edit' => false, 'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.']);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
