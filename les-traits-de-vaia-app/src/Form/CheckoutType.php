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
            ->add('fullname', TextType::class)
            ->add('address', TextType::class)
            ->add('city', TextType::class)
            ->add('postcode', TextType::class)
            ->add('submit', SubmitType::class, ['label' => 'Valider'])
        ;
    }
}
