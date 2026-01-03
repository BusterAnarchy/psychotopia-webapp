<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EstimatorPurityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('molecule', ChoiceType::class, [
                'label' => "Veuillez indiquer la molécule supposée de votre échantillon :",
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-input form-select',
                    'data-auto-submit' => 'molecule',
                ],
                'placeholder' => '— Sélectionnez une molécule —',
                'choices' => [
                    'Cocaïne' => 'cocaine',
                    'MDMA' => 'mdma',
                    'Kétamine' => 'ketamine',
                    'Héroïne' => 'heroine',
                    'Speed' => 'speed',
                    '3-MMC' => '3-mmc',
                    '2C-B' => '2c-b',
                    '4-MMC (Méphédrone)' => '4-mmc'
                ],
            ]);

        if ($options['show_supply']) {
            $builder->add('supply', ChoiceType::class, [
                'label' => "Veuillez indiquer la voie d'approvisionnement de votre échantillon :",
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-input form-select',
                    'data-supply-field' => 'true',
                ],
                'placeholder' => '— Sélectionnez une provenance —',
                'choices' => [
                    'Deep Web / Dark Web' => 'deep web / dark web',
                    'Rue (Four)' => 'dealer de rue (four)',
                    'Livreur' => 'livreur',
                    'Boutique en ligne' => 'boutique en ligne',
                    'Reseaux sociaux en ligne' => 'reseaux sociaux en ligne',
                    'Don entre partenaire de conso' => 'don entre partenaire de conso',
                    'Dealer en soirée' => 'dealer en soiree',
                    'Boutique physique' => 'boutique physique',
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Calculer',
            'attr' => [
                'class' => 'btn btn--primary btn--block',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_supply' => false,
        ]);
    }
}
