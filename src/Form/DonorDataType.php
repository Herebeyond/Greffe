<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for donor data stored as JSON on the Transplant entity.
 * Fields shown depend on donor type (living vs deceased).
 */
class DonorDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $donorType = $options['donor_type'];

        // ===== Common fields =====
        $builder
            ->add('cristalNumber', TextType::class, [
                'label' => 'Numéro CRISTAL',
                'required' => true,
                'attr' => ['placeholder' => 'Ex: CRI-2026-0001'],
            ])
            ->add('bloodGroup', ChoiceType::class, [
                'label' => 'Groupe sanguin',
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
                'label' => 'Commentaire patient',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
        ;

        // ===== HLA Grouping =====
        foreach (['hlaA', 'hlaB', 'hlaDR', 'hlaDQ'] as $hla) {
            $builder->add($hla, IntegerType::class, [
                'label' => strtoupper(str_replace('hla', 'HLA-', $hla)),
                'attr' => ['min' => 0, 'max' => 99],
            ]);
        }
        foreach (['hlaCw', 'hlaDP'] as $hla) {
            $builder->add($hla, IntegerType::class, [
                'label' => strtoupper(str_replace('hla', 'HLA-', $hla)),
                'required' => false,
                'attr' => ['min' => 0, 'max' => 99],
            ]);
        }

        // ===== Serology =====
        $requiredSerology = ['cmv', 'ebv', 'hiv', 'htlv', 'syphilis', 'hcv', 'agHbs', 'acHbs', 'acHbc'];
        foreach ($requiredSerology as $field) {
            $builder->add($field, ChoiceType::class, [
                'label' => $this->getSerologyLabel($field),
                'placeholder' => '...',
                'choices' => ['+' => '+', '-' => '-'],
            ]);
        }

        $builder->add('toxoplasmosis', ChoiceType::class, [
            'label' => 'Toxoplasmose',
            'placeholder' => '...',
            'required' => false,
            'choices' => ['+' => '+', '-' => '-', 'ND' => 'ND'],
        ]);
        $builder->add('arnc', ChoiceType::class, [
            'label' => 'ARNc',
            'placeholder' => '...',
            'required' => false,
            'choices' => ['+' => '+', '-' => '-'],
        ]);
        $builder->add('dnaB', ChoiceType::class, [
            'label' => 'DNA B',
            'placeholder' => '...',
            'required' => false,
            'choices' => ['+' => '+', '-' => '-'],
        ]);

        // ===== Surgical details =====
        $builder
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
            ->add('perfusionLiquid', ChoiceType::class, [
                'label' => 'Liquide de perfusion',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    'Viaspan' => 'Viaspan',
                    'Celsior' => 'Celsior',
                    'IGL' => 'IGL',
                    'Scott' => 'Scott',
                ],
            ])
        ;

        // ===== Living donor specific fields =====
        if ($donorType === 'living') {
            $builder
                ->add('lastName', TextType::class, [
                    'label' => 'Nom du donneur',
                ])
                ->add('firstName', TextType::class, [
                    'label' => 'Prénom du donneur',
                ])
                ->add('relationshipType', ChoiceType::class, [
                    'label' => 'Type de lien',
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        'Parent' => 'Parent',
                        'Enfant' => 'Enfant',
                        '2ème degré' => '2ème degré',
                        'Conjoint' => 'Conjoint',
                        'Non apparenté' => 'Non apparenté',
                        'Autre' => 'Autre',
                    ],
                ])
                ->add('relationshipComment', TextType::class, [
                    'label' => 'Commentaire lien',
                    'required' => false,
                ])
                ->add('creatinine', NumberType::class, [
                    'label' => 'Créatinine (µmol/L)',
                    'scale' => 2,
                    'attr' => ['placeholder' => 'µmol/L'],
                ])
                ->add('isotopicClearance', NumberType::class, [
                    'label' => 'Clairance isotopique (mL/min)',
                    'scale' => 2,
                    'attr' => ['placeholder' => 'mL/min'],
                ])
                ->add('proteinuria', NumberType::class, [
                    'label' => 'Protéinurie (g/24h)',
                    'scale' => 2,
                    'attr' => ['placeholder' => 'g/24h'],
                ])
                ->add('approach', ChoiceType::class, [
                    'label' => 'Voie d\'abord',
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        'Lombotomie' => 'Lombotomie',
                        'Cœlioscopie' => 'Cœlioscopie',
                    ],
                ])
                ->add('robot', CheckboxType::class, [
                    'label' => 'Robot',
                    'required' => false,
                ])
            ;
        }

        // ===== Deceased donor specific fields =====
        if ($donorType === 'deceased_encephalic' || $donorType === 'deceased_cardiac_arrest') {
            $builder
                ->add('originCity', TextType::class, [
                    'label' => 'Ville d\'origine',
                ])
                ->add('deathCause', ChoiceType::class, [
                    'label' => 'Cause du décès',
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        'AVC hémorragique' => 'AVC hémorragique',
                        'AVC ischémique' => 'AVC ischémique',
                        'AVP' => 'AVP',
                        'TC non AVP' => 'TC non AVP',
                        'Anoxie' => 'Anoxie',
                        'Autre' => 'Autre',
                    ],
                ])
                ->add('deathCauseComment', TextareaType::class, [
                    'label' => 'Commentaire cause décès',
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
                    'scale' => 2,
                ])
                ->add('creatinineSample', NumberType::class, [
                    'label' => 'Créatinine au prélèvement (µmol/L)',
                    'scale' => 2,
                ])
                ->add('ureter', ChoiceType::class, [
                    'label' => 'Uretère',
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        '1' => '1',
                        '2' => '2',
                    ],
                ])
                ->add('conservationLiquid', ChoiceType::class, [
                    'label' => 'Liquide de conservation',
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        'Viaspan' => 'Viaspan',
                        'Celsior' => 'Celsior',
                        'IGL' => 'IGL',
                        'Scott' => 'Scott',
                    ],
                ])
                // Atheroma fields
                ->add('aortaAtheroma', CheckboxType::class, [
                    'label' => 'Athérome aorte',
                    'required' => false,
                ])
                ->add('calcifiedAortaPlaques', CheckboxType::class, [
                    'label' => 'Plaques calcifiées aorte',
                    'required' => false,
                ])
                ->add('ostiumArterAtheroma', CheckboxType::class, [
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'donor_type' => null,
        ]);

        $resolver->setAllowedTypes('donor_type', ['string', 'null']);
    }

    private function getSerologyLabel(string $field): string
    {
        return match ($field) {
            'cmv' => 'CMV',
            'ebv' => 'EBV',
            'hiv' => 'HIV',
            'htlv' => 'HTLV',
            'syphilis' => 'Syphilis',
            'hcv' => 'HCV',
            'agHbs' => 'Ag HBs',
            'acHbs' => 'Ac HBs',
            'acHbc' => 'Ac HBc',
            default => $field,
        };
    }
}
