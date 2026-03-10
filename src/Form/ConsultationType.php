<?php

namespace App\Form;

use App\Entity\Consultation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date de consultation',
                'widget' => 'single_text',
            ])
            ->add('practitionerName', TextType::class, [
                'label' => 'Praticien',
                'attr' => [
                    'placeholder' => 'Nom du praticien',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de consultation',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Suivi post-greffe' => 'Suivi post-greffe',
                    'Bilan pré-greffe' => 'Bilan pré-greffe',
                    'Urgence' => 'Urgence',
                    'Contrôle' => 'Contrôle',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Observations cliniques...',
                ],
            ])
            ->add('treatmentNotes', TextareaType::class, [
                'label' => 'Notes de traitement',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Modifications de traitement, prescriptions...',
                ],
            ])
            ->add('nextAppointmentDate', DateType::class, [
                'label' => 'Prochain rendez-vous',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
