<?php

namespace App\Form;

use App\Entity\BiologicalResult;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BiologicalResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date du prélèvement',
                'widget' => 'single_text',
            ])
            ->add('creatinine', NumberType::class, [
                'label' => 'Créatinine (µmol/L)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 120.5'],
            ])
            ->add('creatinineClearance', NumberType::class, [
                'label' => 'Clairance créatinine (mL/min)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 85.0'],
            ])
            ->add('proteinuria', NumberType::class, [
                'label' => 'Protéinurie (g/24h)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'Ex: 0.15'],
            ])
            ->add('hemoglobin', NumberType::class, [
                'label' => 'Hémoglobine (g/dL)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 13.5'],
            ])
            ->add('whiteBloodCells', NumberType::class, [
                'label' => 'Leucocytes (G/L)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 7.5'],
            ])
            ->add('platelets', NumberType::class, [
                'label' => 'Plaquettes (G/L)',
                'required' => false,
                'scale' => 0,
                'attr' => ['placeholder' => 'Ex: 250'],
            ])
            ->add('tacrolimusLevel', NumberType::class, [
                'label' => 'Tacrolimus résiduel (ng/mL)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 8.5'],
            ])
            ->add('ciclosporinLevel', NumberType::class, [
                'label' => 'Ciclosporine résiduelle (ng/mL)',
                'required' => false,
                'scale' => 1,
                'attr' => ['placeholder' => 'Ex: 150.0'],
            ])
            ->add('cmvPcr', ChoiceType::class, [
                'label' => 'PCR CMV',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Positif' => 'Positif',
                    'Négatif' => 'Négatif',
                    'Non effectué' => 'Non effectué',
                ],
            ])
            ->add('ebvPcr', ChoiceType::class, [
                'label' => 'PCR EBV',
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Positif' => 'Positif',
                    'Négatif' => 'Négatif',
                    'Non effectué' => 'Non effectué',
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Commentaire sur les résultats...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BiologicalResult::class,
        ]);
    }
}
