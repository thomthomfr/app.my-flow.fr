<?php

namespace App\Form;

use App\Entity\Company;
use App\Enum\CompanyContract;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Entreprise',
                'required' => true,
            ])
            ->add('siren', TextType::class, [
                'label' => 'Numéro SIREN',
                'required' => false,
            ])
            ->add('logoFile', VichImageType::class, [
                'required' => false,
                'label' => 'Logo de l\'entreprise:',
                'attr' => [
                    'accept' => '.png, .jpg, .jpeg',
                ],
                'mapped' => false,
            ])
            ->add('enabled', ChoiceType::class, [
                'label' => 'Compte actif:',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('CbPayment', ChoiceType::class, [
                'label' => 'Activation du paiement CB:',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('contract', ChoiceType::class, [
                'label' => 'Contrat associé:',
                'choices' => [
                    'Pack de crédit' => CompanyContract::PACK_CREDIT->value,
                    'Facturation fin de mois' => CompanyContract::END_OF_MONTH_BILLING->value,
                    'Facturation mensuelle' => CompanyContract::MONTHLY_BILLING->value,
                    'Comptant' => CompanyContract::CASH->value,
                ],
                'expanded' => true,
                'required' => true,
            ])
            ->add('customerDiscount', TextType::class, [
                'label' => 'Réduction client:'
            ])
            ->add('defaultCreditCost', HiddenType::class, [

            ])
            ->add('costOfDiscountedCredit', HiddenType::class, [

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
