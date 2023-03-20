<?php

namespace App\Form;

use App\Entity\Mission;
use App\Form\DataTransformer\ProductFromFrontIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateMissionType extends AbstractType
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
            ->add('initialBriefing')
            ->add('product', TextType::class)
            ->add('quantity', IntegerType::class)
            ->add('price', NumberType::class)
            ->add('participants', CollectionType::class, [
                'entry_type' => AddParticipantType::class,
                'allow_add' => true,
                'by_reference' => false,
            ])
        ;

        $builder->get('product')
            ->addModelTransformer($this->productFromFrontIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
            'csrf_protection' => false,
        ]);
    }
}
