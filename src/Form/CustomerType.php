<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Customer Name',
                'attr' => [
                    'placeholder' => 'Enter customer name',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Customer name is required'])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
                'attr' => [
                    'placeholder' => 'customer@example.com',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Email(['message' => 'Please enter a valid email address'])
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'placeholder' => '0912-345-6789',
                    'class' => 'form-control'
                ]
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Address',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter customer address',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}