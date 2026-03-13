<?php

namespace App\Form;

use App\Entity\Patient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fileNumber', TextType::class, [
                'label' => 'Numéro de dossier',
                'attr' => [
                    'placeholder' => 'Ex: 2023-001234',
                ],
                'help' => 'Numéro unique d\'identification du patient',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom de famille',
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Prénom',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville de résidence',
                'attr' => [
                    'placeholder' => 'Ex: Paris',
                ],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('sex', ChoiceType::class, [
                'label' => 'Sexe',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Homme' => 'M',
                    'Femme' => 'F',
                ],
            ])
            ->add('bloodGroup', ChoiceType::class, [
                'label' => 'Groupe sanguin',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'A' => 'A',
                    'B' => 'B',
                    'AB' => 'AB',
                    'O' => 'O',
                ],
            ])
            ->add('rhesus', ChoiceType::class, [
                'label' => 'Rhésus',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    '+' => '+',
                    '−' => '-',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 06 12 34 56 78',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: patient@email.fr',
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notes ou commentaires sur le patient...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}
