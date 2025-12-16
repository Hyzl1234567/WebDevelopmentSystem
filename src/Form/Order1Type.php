<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class Order1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function(Customer $customer) {
                    $display = $customer->getName();
                    if ($customer->getPhone()) {
                        $display .= ' - ' . $customer->getPhone();
                    }
                    if ($customer->getEmail()) {
                        $display .= ' (' . $customer->getEmail() . ')';
                    }
                    return $display;
                },
                'label' => 'Customer',
                'placeholder' => 'Select a customer',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Please select a customer'])
                ],
                'help' => 'Don\'t see the customer? <a href="/customer/new" target="_blank">Add new customer</a>'
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function(Product $product) {
                    return $product->getName() . ' - ₱' . number_format($product->getPrice(), 2);
                },
                'label' => 'Product',
                'placeholder' => 'Select a product',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Please select a product'])
                ]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'placeholder' => 'Enter quantity',
                    'class' => 'form-control',
                    'min' => 1,
                    'value' => 1
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Quantity is required']),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Quantity must be at least 1'
                    ])
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Processing' => 'Processing',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ],
                'placeholder' => 'Select status',
                'attr' => ['class' => 'form-control']
            ])
            // Note: totalPrice is NOT added to the form - it will be calculated automatically
            // Note: createdAt is set automatically in the entity constructor
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}