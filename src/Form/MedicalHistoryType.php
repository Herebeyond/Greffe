<?php

namespace App\Form;

use App\Entity\MedicalHistory;
use App\Entity\Reference\MedicalHistoryType as MedicalHistoryTypeRef;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicalHistoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EntityType::class, [
                'class' => MedicalHistoryTypeRef::class,
                'label' => 'Type d\'antécédent',
                'placeholder' => 'Sélectionner...',
                'choice_label' => 'label',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('r')
                    ->where('r.isActive = true')
                    ->orderBy('r.displayOrder', 'ASC'),
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
