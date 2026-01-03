<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class EstimatorMdmaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mass', NumberType::class, [
                'label' => "Veuillez indiquer la masse de votre comprimé d'ecstasy :",
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Masse de votre comprimé en mg',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Calculer',
                'attr' => [
                    'class' => 'btn btn--primary btn--block',
                ],
            ]);
    }
}
