<?php

namespace App\Form;

use App\Entity\MissionParticipant;
use App\Form\DataTransformer\JobFromFrontIdTransformer;
use App\Form\DataTransformer\RoleToStringTransformer;
use App\Form\DataTransformer\UserToEmailTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddParticipantType extends AbstractType
{
    public function __construct(
        private RoleToStringTransformer $roleToStringTransformer,
        private UserToEmailTransformer $userToEmailTransformer,
        private JobFromFrontIdTransformer $jobFromFrontIdTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', TextType::class)
            ->add('user', TextType::class)
            ->add('job', TextType::class)
        ;

        $builder->get('role')
            ->addModelTransformer($this->roleToStringTransformer);
        $builder->get('user')
            ->addModelTransformer($this->userToEmailTransformer);
        $builder->get('job')
            ->addModelTransformer($this->jobFromFrontIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionParticipant::class,
        ]);
    }
}
