<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Form\DataTransformer\UserToEmailTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends AbstractType
{
    public function __construct(
        private UserToEmailTransformer $userToEmailTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('company')
            ->add('brief')
            ->add('missions', CollectionType::class, [
                'entry_type' => CreateMissionType::class,
                'allow_add' => true,
                'by_reference' => false,
            ])
            ->add('attachments', TextType::class, [
                'mapped' => false,
            ])
            ->add('orderedBy', EmailType::class)
            ->add('participants', TextType::class, [
                'mapped' => false,
            ])
        ;

        $builder->get('orderedBy')
            ->addModelTransformer($this->userToEmailTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class,
            'csrf_protection' => false,
        ]);
    }
}
