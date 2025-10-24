<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'required' => true,
                'attr' => ['placeholder' => 'Enter product name'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['rows' => 3, 'placeholder' => 'Write a short description'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'required' => true,
                'scale' => 2,
                'attr' => ['placeholder' => 'Enter price'],
            ])
            ->add('size', TextType::class, [
                'label' => 'Size',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. Small, Medium, Large'],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Category',
                'label' => 'Category',
            ])
            ->add('imageFile', FileType::class, [
        'mapped' => false,   
        'required' => true,
        'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, GIF)',
            ])
        ],
    ]);
             
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}