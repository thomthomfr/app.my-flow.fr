<?php

namespace App\Form;

use App\Entity\MissionParticipant;
use App\Enum\Role;
use App\Form\DataTransformer\UserToEmailTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddMissionContactType extends AbstractType
{
    public function __construct(
        private UserToEmailTransformer $userToEmailTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class, [
                'label' => 'Adresse email de l\'utilisateur',
                'required' => true,
            ])
            ->add('role', EnumType::class, [
                'label' => 'Rôle',
                'class' => Role::class,
                'choices' => [
                    Role::ROLE_OBSERVER,
                    Role::ROLE_VALIDATOR,
                ],
                'choice_label' => function($choice, $key, $value) {
                    return Role::getLabel($choice);
                },
                'required' => true,
            ])
        ;

        if ($options['api']) {
            $builder
                ->add('firstname', TextType::class, [
                    'mapped' => false,
                    'required' => true,
                ])
                ->add('lastname', TextType::class, [
                    'mapped' => false,
                    'required' => true,
                ])
                ->add('phone', TextType::class, [
                    'mapped' => false,
                    'required' => true,
                ])
            ;
        }

        $builder->get('user')
            ->addModelTransformer($this->userToEmailTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionParticipant::class,
            'api' => false,
        ]);
    }
}
