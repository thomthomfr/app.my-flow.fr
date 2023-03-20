<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\MissionParticipant;
use App\Enum\Role;
use App\Form\DataTransformer\UserToEmailTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddMissionSubContractorType extends AbstractType
{
    public function __construct(
        private UserToEmailTransformer $userToEmailTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class, [
                'label' => 'Utilisateur',
            ])
            ->add('job',EntityType::class, [
                'label' => 'Métiers:',
                'class' => Job::class,
                'choice_label' => function ($job) {
                    return html_entity_decode($job->getName());
                },
                'required' => true,
                'by_reference' => false,
            ])
        ;

        $builder->get('user')
            ->addModelTransformer($this->userToEmailTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionParticipant::class,
        ]);
    }
}
