<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom'],
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom de famille'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'exemple@email.fr'],
            ])
            ->add('cristalId', TextType::class, [
                'label' => 'Identifiant CRISTAL',
                'required' => false,
                'attr' => ['placeholder' => 'CRISTAL-123 (optionnel)'],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Admin technique (gestion système)' => 'ROLE_TECH_ADMIN',
                    'Docteur' => 'ROLE_DOCTOR',
                    'Infirmière' => 'ROLE_NURSE',
                    'Patient' => 'ROLE_PATIENT',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
        ;

        // Only add password field for new users or if explicitly requested
        if ($options['require_password']) {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['placeholder' => 'Mot de passe'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['placeholder' => 'Confirmer le mot de passe'],
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe'),
                    new Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'),
                    new Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une lettre majuscule'),
                    new Regex(pattern: '/[a-z]/', message: 'Le mot de passe doit contenir au moins une lettre minuscule'),
                    new Regex(pattern: '/[0-9]/', message: 'Le mot de passe doit contenir au moins un chiffre'),
                    new Regex(pattern: '/[^a-zA-Z0-9]/', message: 'Le mot de passe doit contenir au moins un caractère spécial'),
                ],
            ]);
        } else {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => 'Laisser vide pour ne pas changer'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['placeholder' => 'Confirmer le nouveau mot de passe'],
                ],
                'constraints' => [
                    new Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'),
                    new Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une lettre majuscule'),
                    new Regex(pattern: '/[a-z]/', message: 'Le mot de passe doit contenir au moins une lettre minuscule'),
                    new Regex(pattern: '/[0-9]/', message: 'Le mot de passe doit contenir au moins un chiffre'),
                    new Regex(pattern: '/[^a-zA-Z0-9]/', message: 'Le mot de passe doit contenir au moins un caractère spécial'),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);
    }
}
