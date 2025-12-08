<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Order1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', TextType::class, [
                'label' => 'Customer Name',
                'attr' => [
                    'placeholder' => 'Enter customer name',
                    'class' => 'form-control'
                ]
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name', // Change from 'id' to 'name' to show product names
                'label' => 'Product',
                'placeholder' => 'Select a product',
                'attr' => ['class' => 'form-control']
            ])
            ->add('totalPrice', MoneyType::class, [
                'label' => 'Total Price',
                'currency' => 'PHP',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ],
                'placeholder' => 'Select status',
                'attr' => ['class' => 'form-control']
            ])
            // Removed createdAt - it should be set automatically in the entity
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}