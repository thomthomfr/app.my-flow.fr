<?php

namespace App\Form;

use App\Entity\SystemEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('sender', TextType::class, [
                'label' => 'Expéditeur',
            ])
            ->add('senderName', TextType::class, [
                'label' => 'Nom de l\'expéditeur',
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
            ])
            ->add('content', TextType::class, [
                'label' => 'Message',
            ])
            ->add('smsContent', TextareaType::class, [
                'label' => 'SMS',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Activé',
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SystemEmail::class,
        ]);
    }
}
