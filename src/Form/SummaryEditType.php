<?php

namespace App\Form;

use App\Dto\SummaryEditInput;
use App\Enum\SpoilerLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SummaryEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('content', TextareaType::class, ['label' => 'Contenu', 'attr' => ['rows' => 12]])
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
            'data_class' => SummaryEditInput::class,
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
    }
}
