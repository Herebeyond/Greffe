<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Reference\ConsultationType as ConsultationTypeRef;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date de consultation',
                'widget' => 'single_text',
            ])
            ->add('type', EntityType::class, [
                'class' => ConsultationTypeRef::class,
                'label' => 'Type de consultation',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
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
            ])
            ->add('attachmentFiles', FileType::class, [
                'label' => 'Pièces jointes',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
