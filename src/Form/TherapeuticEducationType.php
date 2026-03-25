<?php

namespace App\Form;

use App\Entity\Reference\EducationTopic;
use App\Entity\Reference\PatientProgress;
use App\Entity\TherapeuticEducation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            ->add('topic', EntityType::class, [
                'class' => EducationTopic::class,
                'label' => 'Thème',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
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
            ->add('patientProgress', EntityType::class, [
                'class' => PatientProgress::class,
                'label' => 'Progression du patient',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
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
