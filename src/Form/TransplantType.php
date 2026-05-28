<?php

namespace App\Form;

use App\Entity\Reference\DonorType as DonorTypeRef;
use App\Entity\Reference\ImmunologicalRisk;
use App\Entity\Reference\ImmunosuppressiveDrug;
use App\Entity\Reference\PeritonealPosition;
use App\Entity\Transplant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class TransplantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAssignmentDraft = $options['is_assignment_draft'];

        $builder
            // ===== Essential information =====
            ->add('transplantDate', DateType::class, [
                'label' => 'Date de greffe',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('rank', IntegerType::class, [
                'label' => 'Rang de greffe',
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 1'],
                'disabled' => $isAssignmentDraft,
            ])
            ->add('donorType', EntityType::class, [
                'class' => DonorTypeRef::class,
                'label' => 'Type de donneur',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'disabled' => $isAssignmentDraft,
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Graft details =====
            ->add('isGraftFunctional', CheckboxType::class, [
                'label' => 'Greffon fonctionnel',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('graftEndDate', DateType::class, [
                'label' => 'Date de fin du greffon',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('graftEndCause', TextareaType::class, [
                'label' => 'Cause de fin du greffon',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'attr' => ['rows' => 2],
            ])
            ->add('surgeonName', TextType::class, [
                'label' => 'Chirurgien',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'attr' => ['placeholder' => 'Nom du chirurgien'],
            ])
            ->add('declampingDate', DateType::class, [
                'label' => 'Date de déclampage',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('declampingTime', TimeType::class, [
                'label' => 'Heure de déclampage',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('harvestSide', ChoiceType::class, [
                'label' => 'Côté de prélèvement',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => [
                    'Droit' => 'droit',
                    'Gauche' => 'gauche',
                ],
            ])
            ->add('transplantSide', ChoiceType::class, [
                'label' => 'Côté de transplantation',
                'placeholder' => 'Sélectionner...',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => [
                    'Droit' => 'droit',
                    'Gauche' => 'gauche',
                ],
            ])
            ->add('peritonealPosition', EntityType::class, [
                'class' => PeritonealPosition::class,
                'label' => 'Position péritonéale',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])
            ->add('totalIschemiaMinutes', IntegerType::class, [
                'label' => 'Ischémie totale (minutes)',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 720'],
            ])
            ->add('anastomosisDuration', IntegerType::class, [
                'label' => 'Durée d\'anastomose (minutes)',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'attr' => ['min' => 1, 'placeholder' => 'Ex: 45'],
            ])
            ->add('jjProbe', CheckboxType::class, [
                'label' => 'Sonde JJ',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'attr' => ['rows' => 3, 'placeholder' => 'Commentaire libre...'],
            ])

            // ===== Virological status (unmapped — handled by controller) =====
            ->add('cmvStatus', ChoiceType::class, [
                'label' => 'Statut CMV',
                'placeholder' => 'Sélectionner...',
                'mapped' => false,
                'disabled' => $isAssignmentDraft,
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
                'mapped' => false,
                'required' => false,
                'disabled' => $isAssignmentDraft,
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
                'mapped' => false,
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => [
                    'R+' => 'R+',
                    'R-' => 'R-',
                ],
            ])

            // ===== HLA incompatibility (unmapped — handled by controller) =====
            ->add('hlaA', ChoiceType::class, [
                'label' => 'HLA-A',
                'placeholder' => '...',
                'mapped' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaB', ChoiceType::class, [
                'label' => 'HLA-B',
                'placeholder' => '...',
                'mapped' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaCw', ChoiceType::class, [
                'label' => 'HLA-Cw',
                'placeholder' => '...',
                'mapped' => false,
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDR', ChoiceType::class, [
                'label' => 'HLA-DR',
                'placeholder' => '...',
                'mapped' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDQ', ChoiceType::class, [
                'label' => 'HLA-DQ',
                'placeholder' => '...',
                'mapped' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])
            ->add('hlaDP', ChoiceType::class, [
                'label' => 'HLA-DP',
                'placeholder' => '...',
                'mapped' => false,
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choices' => ['0' => 0, '1' => 1, '2' => 2],
            ])

            // ===== Immunological risk =====
            ->add('immunologicalRisk', EntityType::class, [
                'class' => ImmunologicalRisk::class,
                'label' => 'Risque immunologique',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Immunosuppressive conditioning =====
            ->add('immunosuppressiveDrugs', EntityType::class, [
                'class' => ImmunosuppressiveDrug::class,
                'label' => 'Conditionnement immunosuppresseur',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'disabled' => $isAssignmentDraft,
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
            ])

            // ===== Dialysis =====
            ->add('dialysis', CheckboxType::class, [
                'label' => 'Dialyse',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])
            ->add('lastDialysisDate', DateType::class, [
                'label' => 'Date de dernière dialyse',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])

            // ===== Protocol =====
            ->add('hasProtocol', CheckboxType::class, [
                'label' => 'Protocole',
                'required' => false,
                'disabled' => $isAssignmentDraft,
            ])

            // ===== File uploads (unmapped) =====
            ->add('operativeReportFiles', FileType::class, [
                'label' => 'Compte-rendu opératoire',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'disabled' => $isAssignmentDraft,
                'constraints' => [
                    new All([
                        new File(
                            maxSize: '10M',
                            mimeTypes: [
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ],
                            mimeTypesMessage: 'Veuillez envoyer un fichier PDF, JPEG ou PNG.',
                        ),
                    ]),
                ],
            ])
            ->add('protocolFiles', FileType::class, [
                'label' => 'Fichier protocole',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'disabled' => $isAssignmentDraft,
                'constraints' => [
                    new All([
                        new File(
                            maxSize: '10M',
                            mimeTypes: [
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ],
                            mimeTypesMessage: 'Veuillez envoyer un fichier PDF, JPEG ou PNG.',
                        ),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transplant::class,
            'is_assignment_draft' => false,
        ]);

        $resolver->setAllowedTypes('is_assignment_draft', 'bool');
    }
}
