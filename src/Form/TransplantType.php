<?php

namespace App\Form;

use App\Entity\Transplant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransplantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ===== Essential information =====
            ->add('transplantDate', DateType::class, [
                'label' => 'Date de greffe',
                'widget' => 'single_text',
            ])
            ->add('rank', IntegerType::class, [
                'label' => 'Rang de greffe',
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 1'],
            ])
            ->add('donorType', ChoiceType::class, [
                'label' => 'Type de donneur',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Donneur vivant' => 'living',
                    'Donneur décédé (mort encéphalique)' => 'deceased_encephalic',
                    'Donneur décédé (arrêt cardiaque)' => 'deceased_cardiac_arrest',
                ],
            ])

            // ===== Graft details =====
            ->add('isGraftFunctional', CheckboxType::class, [
                'label' => 'Greffon fonctionnel',
                'required' => false,
            ])
            ->add('graftEndDate', DateType::class, [
                'label' => 'Date de fin du greffon',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('graftEndCause', TextareaType::class, [
                'label' => 'Cause de fin du greffon',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('transplantType', ChoiceType::class, [
                'label' => 'Type de transplantation',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Rein' => 'Rein',
                    'Rein donneur vivant' => 'Rein donneur vivant',
                    'Rein-pancréas' => 'Rein-pancréas',
                    'Rein-foie' => 'Rein-foie',
                    'Rein-coeur' => 'Rein-coeur',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('surgeonName', TextType::class, [
                'label' => 'Chirurgien',
                'required' => false,
                'attr' => ['placeholder' => 'Nom du chirurgien'],
            ])
            ->add('declampingDate', DateType::class, [
                'label' => 'Date de déclampage',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('declampingTime', TimeType::class, [
                'label' => 'Heure de déclampage',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('harvestSide', ChoiceType::class, [
                'label' => 'Côté de prélèvement',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Droit' => 'droit',
                    'Gauche' => 'gauche',
                ],
            ])
            ->add('transplantSide', ChoiceType::class, [
                'label' => 'Côté de transplantation',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Droit' => 'droit',
                    'Gauche' => 'gauche',
                ],
            ])
            ->add('peritonealPosition', ChoiceType::class, [
                'label' => 'Position péritonéale',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Extra Péritonéal' => 'Extra Péritonéal',
                    'Intra Péritonéal' => 'Intra Péritonéal',
                ],
            ])
            ->add('totalIschemiaMinutes', IntegerType::class, [
                'label' => 'Ischémie totale (minutes)',
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 720'],
            ])
            ->add('anastomosisDuration', IntegerType::class, [
                'label' => 'Durée d\'anastomose (minutes)',
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 45'],
            ])
            ->add('jjProbe', CheckboxType::class, [
                'label' => 'Sonde JJ',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Commentaire libre...'],
            ])

            // ===== Virological status =====
            ->add('cmvStatus', ChoiceType::class, [
                'label' => 'Statut CMV',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'D-/R-' => 'D-/R-',
                    'D-/R+' => 'D-/R+',
                    'D+/R-' => 'D+/R-',
                    'D+/R+' => 'D+/R+',
                ],
            ])
            ->add('ebvStatus', ChoiceType::class, [
                'label' => 'Statut EBV',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    'D-/R-' => 'D-/R-',
                    'D-/R+' => 'D-/R+',
                    'D+/R-' => 'D+/R-',
                    'D+/R+' => 'D+/R+',
                ],
            ])
            ->add('toxoplasmosisStatus', ChoiceType::class, [
                'label' => 'Statut toxoplasmose',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'choices' => [
                    'R+' => 'R+',
                    'R-' => 'R-',
                ],
            ])

            // ===== HLA incompatibility =====
            ->add('hlaA', ChoiceType::class, [
                'label' => 'HLA-A',
                'placeholder' => '...',
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaB', ChoiceType::class, [
                'label' => 'HLA-B',
                'placeholder' => '...',
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaCw', ChoiceType::class, [
                'label' => 'HLA-Cw',
                'placeholder' => '...',
                'required' => false,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDR', ChoiceType::class, [
                'label' => 'HLA-DR',
                'placeholder' => '...',
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDQ', ChoiceType::class, [
                'label' => 'HLA-DQ',
                'placeholder' => '...',
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDP', ChoiceType::class, [
                'label' => 'HLA-DP',
                'placeholder' => '...',
                'required' => false,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])

            // ===== Immunological risk =====
            ->add('immunologicalRisk', ChoiceType::class, [
                'label' => 'Risque immunologique',
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Non immunisé' => 'Non immunisé',
                    'Immunisé sans DSA' => 'Immunisé sans DSA',
                    'Immunisé DSA' => 'Immunisé DSA',
                    'ABO incompatible' => 'ABO incompatible',
                ],
            ])

            // ===== Immunosuppressive conditioning =====
            ->add('immunosuppressiveConditioning', ChoiceType::class, [
                'label' => 'Conditionnement immunosuppresseur',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => [
                    'Advagraf' => 'Advagraf',
                    'Prograf' => 'Prograf',
                    'Neoral' => 'Neoral',
                    'Rapamune' => 'Rapamune',
                    'Certican' => 'Certican',
                    'Cellcept' => 'Cellcept',
                    'Myfortic' => 'Myfortic',
                    'Imurel' => 'Imurel',
                    'Methylprednisolone' => 'Methylprednisolone',
                    'Mabthera' => 'Mabthera',
                    'Ig IV' => 'Ig IV',
                    'Soliris' => 'Soliris',
                    'Thymoglobulines' => 'Thymoglobulines',
                    'Simulect' => 'Simulect',
                    'Plasmaphérèse' => 'Plasmaphérèse',
                    'Immuno absorption' => 'Immuno absorption',
                ],
            ])

            // ===== Dialysis =====
            ->add('dialysis', CheckboxType::class, [
                'label' => 'Dialyse',
                'required' => false,
            ])
            ->add('lastDialysisDate', DateType::class, [
                'label' => 'Date de dernière dialyse',
                'widget' => 'single_text',
                'required' => false,
            ])

            // ===== Protocol =====
            ->add('hasProtocol', CheckboxType::class, [
                'label' => 'Protocole',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transplant::class,
        ]);
    }
}
