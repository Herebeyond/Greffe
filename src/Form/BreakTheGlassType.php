<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BreakTheGlassType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('justification', TextareaType::class, [
                'label' => 'Justification de l\'accès d\'urgence',
                'attr' => [
                    'placeholder' => 'Décrivez la raison de cet accès exceptionnel (ex: patient inconscient aux urgences, prise en charge urgente...)',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank(message: 'La justification est obligatoire'),
                    new Length(
                        min: 10,
                        minMessage: 'La justification doit contenir au moins {{ limit }} caractères',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
