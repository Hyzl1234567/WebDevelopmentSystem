<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name', // Changed from 'id' to 'name'
                'label' => 'Product',
                'placeholder' => 'Select a Product',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'placeholder' => 'Enter quantity',
                    'min' => 0
                ],
            ])
            ->add('lastUpdated', null, [
                'widget' => 'single_text',
                'label' => 'Last Updated',
                'disabled' => true, // Auto-set, so make it read-only
                'attr' => [
                    'class' => 'readonly-field'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}