<?php

namespace App\Form;

use App\Entity\Figurine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FigurineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['class' => 'form-control', 'rows' => 5]])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo de la figurine', 'mapped' => true, 'required' => $options['is_new'],
                'data_class' => null, 'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], mimeTypesMessage: 'Choisissez une image JPEG, PNG, WebP ou GIF.')],
                'help' => $options['is_new'] ? 'Image obligatoire, 5 Mo maximum.' : 'Laisser vide pour conserver la photo actuelle.',
            ])
            ->add('prix', NumberType::class, ['label' => 'Prix (€)', 'scale' => 2, 'html5' => true, 'attr' => ['class' => 'form-control', 'min' => '0', 'step' => '0.01']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Figurine::class, 'is_new' => false, 'validation_groups' => static fn ($form) => $form->getConfig()->getOption('is_new') ? ['Default', 'figurine_create'] : ['Default']]);
    }
}
