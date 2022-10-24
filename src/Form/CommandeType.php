<?php

namespace App\Form;

use App\Entity\Commande;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_prise_commande', DateTimeType::class, array(
                'widget' => 'single_text',
                'data' => new DateTime(),
                'required' => true
            ))
            ->add('client', null, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('date_livraison_souhaitee', DateTimeType::class, array(
                'widget' => 'single_text',
                'label' => 'Date de livraison souhaitée',
                'required' => true
            ))
            ->add('livraison_domicile', CheckboxType::class, [
                'required' => false
            ])
            ->add('montant', MoneyType::class, [
                'required' => true
            ])
            ->add('commande', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 10
                ]
            ])
            ->add('mode_paiement', ChoiceType::class, [
                'choices' =>
                    [
                        'Non payé' => null,
                        'Acompte' => 'ACC',
                        'Espèces' => 'ESP',
                        'Carte bancaire' => 'CB',
                        'Chèque' => 'CHQ',
			'Virement' => 'VIR'
                    ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Mode de paiement'
            ])
            ->add('prete', CheckboxType::class, [
                'label' => 'Commande prête',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
