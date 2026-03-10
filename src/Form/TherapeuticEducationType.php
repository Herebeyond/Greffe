<?php

namespace App\Form;

use App\Entity\TherapeuticEducation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TherapeuticEducationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sessionDate', DateType::class, [
                'label' => 'Date de la séance',
                'widget' => 'single_text',
            ])
            ->add('topic', ChoiceType::class, [
                'label' => 'Thème',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Observance médicamenteuse' => 'Observance médicamenteuse',
                    'Hygiène de vie' => 'Hygiène de vie',
                    'Signes de rejet' => 'Signes de rejet',
                    'Diététique' => 'Diététique',
                    'Activité physique' => 'Activité physique',
                    'Gestion du stress' => 'Gestion du stress',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('educator', TextType::class, [
                'label' => 'Éducateur',
                'attr' => [
                    'placeholder' => 'Nom de l\'éducateur',
                ],
            ])
            ->add('objectives', TextareaType::class, [
                'label' => 'Objectifs',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Objectifs de la séance...',
                ],
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Observations sur le déroulement...',
                ],
            ])
            ->add('patientProgress', ChoiceType::class, [
                'label' => 'Progression du patient',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Acquis' => 'Acquis',
                    'En cours' => 'En cours',
                    'Non acquis' => 'Non acquis',
                ],
            ])
            ->add('nextSessionDate', DateType::class, [
                'label' => 'Prochaine séance',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TherapeuticEducation::class,
        ]);
    }
}
