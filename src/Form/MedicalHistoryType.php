<?php

namespace App\Form;

use App\Entity\MedicalHistory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicalHistoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'antécédent',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Médical' => 'Médical',
                    'Chirurgical' => 'Chirurgical',
                    'Familial' => 'Familial',
                    'Allergique' => 'Allergique',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description de l\'antécédent...',
                ],
            ])
            ->add('diagnosisDate', DateType::class, [
                'label' => 'Date de diagnostic',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Commentaire optionnel...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicalHistory::class,
        ]);
    }
}
