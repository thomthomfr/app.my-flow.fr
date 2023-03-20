<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Service;
use App\Enum\BillingMethod;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'label' => false,
                'class' => Product::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('p')
                        ->join('p.jobs', 'j')
                        ->andWhere('j IN (:jobs)')
                            ->setParameter('jobs', $options['subContractor']?->getJobs())
                        ->orderBy('p.name', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => false,
                'required' => true,
                'by_reference' => false,
                'expanded' => false,
            ])

            ->add('serviceId', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Ajouter'])
        ;

        if ($options['subContractor']?->getBillingMethod() != BillingMethod::BILL_TIME_PAST->value) {
            $builder
                ->add('price', TextType::class, [
                    'label' => false
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
            'subContractor' => null,
            'admin' => null,
        ]);
    }
}
