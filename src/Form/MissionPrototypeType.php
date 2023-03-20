<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\Job;
use App\Entity\Mission;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Workflow;
use App\Enum\Role;
use App\Form\DataTransformer\ProductFromFrontIdTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionPrototypeType extends AbstractType
{
    public function __construct(
        private ProductFromFrontIdTransformer $productFromFrontIdTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('desiredDelivery', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('product', EntityType::class, [
                'label' => 'Produit',
                'class' => Product::class,
                'choice_label' => 'name',
                'choice_attr' => function ($choice, $key, $value) use ($options) {
                    return [
                        'data-price' => $options['company']->getContract() == Company::PACK_CREDIT ? number_format($choice->getPrice() / 220, 2, '.', ' ') : $choice->getPrice(),
                        'data-job' => $choice->getJobs()?->first() !== false ? $choice->getJobs()?->first()->getId() : null,
                    ];
                },
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('quantity', IntegerType::class)
            ->add('newWorkflow', EntityType::class, [
                'mapped' => false,
                'placeholder' => 'Laissez vide pour ne pas modifier',
                'label' => 'Workflow',
                'class' => Workflow::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('w')
                        ->andWhere('w.template = :true')
                            ->setParameter('true', true)
                    ;
                },
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
            ])
            ->add('newJob', EntityType::class, [
                'mapped' => false,
                'label' => 'Métier',
                'class' => Job::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
            ])
            ->add('newSubContractor', EntityType::class, [
                'mapped' => false,
                'label' => 'Sous-traitant',
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%'.Role::ROLE_SUBCONTRACTOR->value.'%')
                        ->addOrderBy('u.lastname', 'ASC');
                },                'choice_label' => 'email',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
            ])
            ->add('libelleCustom', TextareaType::class, [
                'required' => false,
            ])
            ->add('missionId', HiddenType::class, [
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
            'company' => null,
        ]);
    }
}
