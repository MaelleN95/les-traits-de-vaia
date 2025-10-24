<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fullname', TextType::class,
                [
                    'label' => 'Nom complet',
                ]
            )
            ->add('address', TextType::class,
                [
                    'label' => 'Adresse',
                ]
            )
            ->add('city', TextType::class,
                [
                    'label' => 'Ville',
                ]
            )
            ->add('postcode', TextType::class,
                [
                    'label' => 'Code postal',
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Valider'])
        ;
    }
}
