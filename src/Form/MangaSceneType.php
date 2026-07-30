<?php

namespace App\Form;

use App\Dto\MangaSceneInput;
use App\Enum\SpoilerLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MangaSceneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, ['label' => 'Miniature de la scène'])
            ->add('targetType', ChoiceType::class, [
                'label' => 'Cible narrative',
                'choices' => ['Tome' => 'tome', 'Chapitre' => 'chapitre'],
                'expanded' => true,
                'attr' => ['data-action' => 'change->manga-scene-target#change'],
            ])
            ->add('tomeId', ChoiceType::class, [
                'label' => 'Tome',
                'choices' => $options['tome_choices'],
                'placeholder' => 'Choisissez un tome',
                'required' => false,
            ])
            ->add('chapitreId', ChoiceType::class, [
                'label' => 'Chapitre',
                'choices' => $options['chapitre_choices'],
                'placeholder' => 'Choisissez un chapitre',
                'required' => false,
            ])
            ->add('title', TextType::class, ['label' => 'Titre de la scène'])
            ->add('content', TextareaType::class, [
                'label' => 'Résumé de la scène',
                'attr' => ['rows' => 10],
            ])
            ->add('spoilerLevel', ChoiceType::class, [
                'label' => 'Niveau de spoiler',
                'choices' => [
                    'Aucun' => SpoilerLevel::Aucun,
                    'Mineur' => SpoilerLevel::Mineur,
                    'Majeur' => SpoilerLevel::Majeur,
                ],
                'choice_value' => static fn (?SpoilerLevel $level): string => $level?->value ?? '',
                'expanded' => true,
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MangaSceneInput::class,
            'tome_choices' => [],
            'chapitre_choices' => [],
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
        $resolver->setAllowedTypes('tome_choices', 'array');
        $resolver->setAllowedTypes('chapitre_choices', 'array');
    }
}
