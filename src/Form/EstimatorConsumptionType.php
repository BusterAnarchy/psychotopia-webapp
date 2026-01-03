<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class EstimatorConsumptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('molecule', ChoiceType::class, [
                'label' => "Molécule",
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '— Choisis une molécule —',
                'choices' => [
                    'Cocaïne' => 'cocaine',
                    'MDMA' => 'mdma',
                    'Kétamine' => 'ketamine',
                    'Héroïne' => 'heroine',
                    'Speed' => 'speed',
                    '3-MMC' => '3-mmc',
                    '2C-B' => '2c-b',
                    '4-MMC (Méphédrone)' => '4-mmc',
                ],
            ])
            ->add('form_type', ChoiceType::class, [
                'label' => "Forme",
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '— Choisis une forme —',
                'choices' => [
                    'Comprimé' => 'comprime',
                    'Cristaux' => 'cristal',
                ],
            ])
            ->add('supply', ChoiceType::class, [
                'label' => "Provenance",
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '— Choisis une provenance —',
                'choices' => [
                    'Toutes provenances' => 'all',
                    'Deep Web / Dark Web' => 'deep web / dark web',
                    'Rue (Four)' => 'dealer de rue (four)',
                    'Livreur' => 'livreur',
                    'Boutique en ligne' => 'boutique en ligne',
                    'Reseaux sociaux en ligne' => 'reseaux sociaux en ligne',
                    'Don entre partenaire de conso' => 'don entre partenaire de conso',
                    'Dealer en soirée' => 'dealer en soiree',
                    'Boutique physique' => 'boutique physique',
                ],
            ])
            ->add('mass', NumberType::class, [
                'label' => 'Masse du comprimé (mg)',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex: 250',
                    'min' => 0,
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
