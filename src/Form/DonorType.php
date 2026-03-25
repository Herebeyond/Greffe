<?php

namespace App\Form;

use App\Entity\Donor;
use App\Entity\Reference\BloodGroup;
use App\Entity\Reference\DeathCause;
use App\Entity\Reference\DonorType as DonorTypeRef;
use App\Entity\Reference\PerfusionLiquid;
use App\Entity\Reference\RelationshipType;
use App\Entity\Reference\SurgicalApproach;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ===== Donor type =====
            ->add('donorType', EntityType::class, [
                'class' => DonorTypeRef::class,
                'label' => 'Type de donneur',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Common identification =====
            ->add('cristalNumber', TextType::class, [
                'label' => 'Numéro CRISTAL',
                'attr' => ['placeholder' => 'Ex: CRI-2026-0001'],
            ])
            ->add('bloodGroup', EntityType::class, [
                'class' => BloodGroup::class,
                'label' => 'Groupe sanguin',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])
            ->add('rhesus', ChoiceType::class, [
                'label' => 'Rhésus',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    '+' => '+',
                    '−' => '-',
                ],
            ])
            ->add('sex', ChoiceType::class, [
                'label' => 'Sexe',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge',
                'attr' => ['min' => 0, 'max' => 120],
            ])
            ->add('height', IntegerType::class, [
                'label' => 'Taille (cm)',
                'required' => false,
                'attr' => ['min' => 50, 'max' => 250, 'placeholder' => 'cm'],
            ])
            ->add('weight', IntegerType::class, [
                'label' => 'Poids (kg)',
                'required' => false,
                'attr' => ['min' => 10, 'max' => 300, 'placeholder' => 'kg'],
            ])
            ->add('patientComment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // ===== HLA Grouping (unmapped — handled by controller) =====
            ->add('hlaA', IntegerType::class, [
                'label' => 'HLA-A',
                'mapped' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])
            ->add('hlaB', IntegerType::class, [
                'label' => 'HLA-B',
                'mapped' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])
            ->add('hlaCw', IntegerType::class, [
                'label' => 'HLA-Cw',
                'mapped' => false,
                'required' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])
            ->add('hlaDR', IntegerType::class, [
                'label' => 'HLA-DR',
                'mapped' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])
            ->add('hlaDQ', IntegerType::class, [
                'label' => 'HLA-DQ',
                'mapped' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])
            ->add('hlaDP', IntegerType::class, [
                'label' => 'HLA-DP',
                'mapped' => false,
                'required' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ])

            // ===== Serology (unmapped — handled by controller) =====
            ->add('cmv', ChoiceType::class, [
                'label' => 'CMV',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('ebv', ChoiceType::class, [
                'label' => 'EBV',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('hiv', ChoiceType::class, [
                'label' => 'HIV',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('htlv', ChoiceType::class, [
                'label' => 'HTLV',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('syphilis', ChoiceType::class, [
                'label' => 'Syphilis',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('hcv', ChoiceType::class, [
                'label' => 'HCV',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('agHbs', ChoiceType::class, [
                'label' => 'Ag HBs',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('acHbs', ChoiceType::class, [
                'label' => 'Ac HBs',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('acHbc', ChoiceType::class, [
                'label' => 'Ac HBc',
                'placeholder' => '...',
                'mapped' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('toxoplasmosis', ChoiceType::class, [
                'label' => 'Toxoplasmose',
                'placeholder' => '...',
                'mapped' => false,
                'required' => false,
                'choices' => ['+' => '+', '-' => '-', 'ND' => 'ND'],
            ])
            ->add('arnc', ChoiceType::class, [
                'label' => 'ARNc',
                'placeholder' => '...',
                'mapped' => false,
                'required' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])
            ->add('dnaB', ChoiceType::class, [
                'label' => 'DNA B',
                'placeholder' => '...',
                'mapped' => false,
                'required' => false,
                'choices' => ['+' => '+', '-' => '-'],
            ])

            // ===== Surgical details =====
            ->add('donorSurgeonName', TextType::class, [
                'label' => 'Chirurgien (prélèvement)',
                'required' => false,
            ])
            ->add('clampingDate', DateType::class, [
                'label' => 'Date de clampage',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('clampingTime', TimeType::class, [
                'label' => 'Heure de clampage',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('donorHarvestSide', ChoiceType::class, [
                'label' => 'Côté de prélèvement (donneur)',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    'Droit' => 'droit',
                    'Gauche' => 'gauche',
                ],
            ])
            ->add('mainArtery', TextType::class, [
                'label' => 'Artère principale',
                'required' => false,
            ])
            ->add('upperPolarArtery', TextType::class, [
                'label' => 'Artère polaire supérieure',
                'required' => false,
            ])
            ->add('lowerPolarArtery', TextType::class, [
                'label' => 'Artère polaire inférieure',
                'required' => false,
            ])
            ->add('vein', TextType::class, [
                'label' => 'Veine',
                'required' => false,
            ])
            ->add('veinComment', TextType::class, [
                'label' => 'Commentaire veine',
                'required' => false,
            ])
            ->add('perfusionMachine', ChoiceType::class, [
                'label' => 'Machine de perfusion',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    'Oui' => 'Oui',
                    'Non' => 'Non',
                ],
            ])
            ->add('perfusionLiquid', EntityType::class, [
                'class' => PerfusionLiquid::class,
                'label' => 'Liquide de perfusion',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Living donor specific fields =====
            ->add('lastName', TextType::class, [
                'label' => 'Nom du donneur',
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom du donneur',
                'required' => false,
            ])
            ->add('relationshipType', EntityType::class, [
                'class' => RelationshipType::class,
                'label' => 'Type de lien',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])
            ->add('relationshipComment', TextType::class, [
                'label' => 'Commentaire lien',
                'required' => false,
            ])
            ->add('creatinine', NumberType::class, [
                'label' => 'Créatinine (µmol/L)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'µmol/L'],
            ])
            ->add('isotopicClearance', NumberType::class, [
                'label' => 'Clairance isotopique (mL/min)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'mL/min'],
            ])
            ->add('proteinuria', NumberType::class, [
                'label' => 'Protéinurie (g/24h)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'g/24h'],
            ])
            ->add('approach', EntityType::class, [
                'class' => SurgicalApproach::class,
                'label' => 'Voie d\'abord',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])
            ->add('robot', CheckboxType::class, [
                'label' => 'Robot',
                'required' => false,
            ])

            // ===== Deceased donor specific fields =====
            ->add('originCity', TextType::class, [
                'label' => 'Ville d\'origine',
                'required' => false,
            ])
            ->add('deathCause', EntityType::class, [
                'class' => DeathCause::class,
                'label' => 'Cause du décès',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])
            ->add('deathCauseComment', TextareaType::class, [
                'label' => 'Commentaire cause décès',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('extendedCriteriaDonor', CheckboxType::class, [
                'label' => 'Donneur à critères élargis (DCE)',
                'required' => false,
            ])
            ->add('cardiacArrest', CheckboxType::class, [
                'label' => 'Arrêt cardiaque',
                'required' => false,
            ])
            ->add('cardiacArrestDuration', IntegerType::class, [
                'label' => 'Durée arrêt cardiaque (min)',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('meanArterialPressure', NumberType::class, [
                'label' => 'Pression artérielle moyenne (mmHg)',
                'required' => false,
                'scale' => 1,
            ])
            ->add('amines', CheckboxType::class, [
                'label' => 'Amines',
                'required' => false,
            ])
            ->add('transfusion', CheckboxType::class, [
                'label' => 'Transfusion',
                'required' => false,
            ])
            ->add('cgr', IntegerType::class, [
                'label' => 'CGR',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('cpa', IntegerType::class, [
                'label' => 'CPA',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('pfc', IntegerType::class, [
                'label' => 'PFC',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('creatinineArrival', NumberType::class, [
                'label' => 'Créatinine à l\'arrivée (µmol/L)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('creatinineSample', NumberType::class, [
                'label' => 'Créatinine au prélèvement (µmol/L)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('ureter', ChoiceType::class, [
                'label' => 'Uretère',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    '1' => '1',
                    '2' => '2',
                ],
            ])
            ->add('conservationLiquid', EntityType::class, [
                'class' => PerfusionLiquid::class,
                'label' => 'Liquide de conservation',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Atheroma =====
            ->add('aortaAtheroma', CheckboxType::class, [
                'label' => 'Athérome aorte',
                'required' => false,
            ])
            ->add('calcifiedAortaPlaques', CheckboxType::class, [
                'label' => 'Plaques calcifiées aorte',
                'required' => false,
            ])
            ->add('ostiumArteryAtheroma', CheckboxType::class, [
                'label' => 'Athérome ostium artère',
                'required' => false,
            ])
            ->add('calcifiedOstiumPlaques', CheckboxType::class, [
                'label' => 'Plaques calcifiées ostium',
                'required' => false,
            ])
            ->add('renalArteryAtheroma', CheckboxType::class, [
                'label' => 'Athérome artère rénale',
                'required' => false,
            ])
            ->add('calcifiedRenalPlaques', CheckboxType::class, [
                'label' => 'Plaques calcifiées rénales',
                'required' => false,
            ])
            ->add('digestiveWound', CheckboxType::class, [
                'label' => 'Plaie digestive',
                'required' => false,
            ])
            ->add('conservationLiquidInfection', CheckboxType::class, [
                'label' => 'Infection liquide de conservation',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Donor::class,
        ]);
    }
}
