<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'Nom',
                'required' => true
            ])
            ->add('prenom', null, [
                'label' => 'Prénom',
                'required' => true
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false
            ])
            ->add('email', EmailType::class, [
                'label' => 'Courriel',
                'required' => false
            ])
            ->add('pseudo_facebook', null, [
                'label' => 'Pseudo Facebook',
                'required' => false
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse postale',
                'required' => false
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
