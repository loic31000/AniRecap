<?php

namespace App\Form;

use App\Dto\DiaporamaInput;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DiaporamaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sourceType', ChoiceType::class, [
                'label' => 'Type de source',
                'choices' => ['Animé' => 'anime', 'Manga' => 'manga'],
                'expanded' => true,
            ])
            ->add('title', TextType::class, ['label' => 'Titre du diaporama'])
            ->add('image', FileType::class, [
                'label' => 'Miniature du diaporama',
                'help' => 'PNG ou JPG, 2 Mio maximum.',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Texte du diaporama',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'label' => 'Catégories',
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiaporamaInput::class,
            'csrf_token_id' => 'diaporama_create',
            'csrf_message' => 'Votre session a expiré. Rechargez la page puis réessayez.',
        ]);
    }
}
