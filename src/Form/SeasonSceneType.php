<?php

namespace App\Form;

use App\Dto\SeasonSceneInput;
use App\Enum\SpoilerLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SeasonSceneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, ['label' => 'Miniature de la scène'])
            ->add('episodeId', ChoiceType::class, [
                'label' => 'Épisode',
                'choices' => $options['episode_choices'],
                'placeholder' => 'Choisissez un épisode',
            ])
            ->add('startTimecode', TextType::class, [
                'label' => 'Timecode de début',
                'attr' => ['placeholder' => '04:20 ou 01:04:20'],
            ])
            ->add('endTimecode', TextType::class, [
                'label' => 'Timecode de fin',
                'required' => false,
                'attr' => ['placeholder' => '22:01 ou 01:22:01'],
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
            'data_class' => SeasonSceneInput::class,
            'episode_choices' => [],
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
        $resolver->setAllowedTypes('episode_choices', 'array');
    }
}
