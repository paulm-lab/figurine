<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, ['label' => 'Prénom', 'attr' => ['class' => 'form-control']])
            ->add('lastname', TextType::class, ['label' => 'Nom', 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'attr' => ['class' => 'form-control', 'autocomplete' => 'email']])
            ->add('imageFile', FileType::class, ['label' => 'Photo de profil', 'mapped' => true, 'required' => true, 'data_class' => null, 'attr' => ['class' => 'form-control', 'accept' => 'image/*'], 'constraints' => [new Assert\File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])]])
            ->add('plainPassword', PasswordType::class, ['mapped' => false, 'label' => 'Mot de passe', 'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'], 'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'validation_groups' => ['Default', 'registration']]);
    }
}
