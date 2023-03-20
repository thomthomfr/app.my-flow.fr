<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\Product;
use App\Entity\SubContractorCompany;
use App\Enum\BillingMethod;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubContractorCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobs',EntityType::class, [
                'label' => 'Métiers:',
                'class' => Job::class,
                'choice_label' => function ($job) {
                    return html_entity_decode($job->getName());
                },
                'multiple' => true,
                'required' => true,
                'by_reference' => false,
            ])
            ->add('products',EntityType::class, [
                'label' => 'Produit:',
                'class' => Product::class,
                'choice_label' => function ($product) {
                    return html_entity_decode($product->getName());
                },
                'multiple' => true,
                'required' => true,
                'by_reference' => false,
            ])
            ->add('billingMethod', ChoiceType::class, [
                'label' => 'Type de facturation',
                'choices' => [
                    'Facturation au temps passé' => BillingMethod::BILL_TIME_PAST->value,
                    'Facturation à la prestation' => BillingMethod::BILL_PRESTATION->value,
                ],
                'placeholder' => 'Choisissez',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubContractorCompany::class,
        ]);
    }
}
