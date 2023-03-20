<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\TypeUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddUserCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom:',
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom:',
            ])
            ->add('cellPhone', TextType::class, [
                'label' => 'Téléphone:',
                'attr' => [
                    'pattern' => '^(?:\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$',
                ],
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse email:',
            ])
            ->add('accountType', ChoiceType::class, [
                'label' => 'Type:',
                'choices' => [
                    'Interne' => TypeUser::USER_INTERNE->value,
                    'Externe' => TypeUser::USER_EXTERNE->value,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
