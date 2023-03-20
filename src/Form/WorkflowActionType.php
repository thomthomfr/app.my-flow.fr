<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\WorkflowAction;
use App\Enum\Role;
use App\Form\DataTransformer\WorkflowStepToNumberTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowActionType extends AbstractType
{
    public function __construct(
        private WorkflowStepToNumberTransformer $stepToNumberTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'action',
                'required' => true,
            ])
            ->add('recipient', EnumType::class, [
                'label' => 'Pour ? <i class="fas fa-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-html="true" title="<strong>Client :</strong> Tous les contacts clients de la mission.<br><strong>Sous-traitant :</strong> Si un métier est renseigné, le(s) sous-traitant(s) de ce métier. Sinon tous les sous-traitants de la mission.<br><strong>Administrateur :</strong> Tous les utilisateurs de la plateforme déclarés comme administrateurs.<br><strong>Validateur :</strong> Tous les contacts clients ayant le rôle de validateur de la mission.<br><strong>Observateur :</strong> Tous les contacts clients ayant le rôle d\'observateur de la mission.<br><strong>Tous les partenaires :</strong> Tous les contacts clients et tous les sous-traitants de la mission."></i>',
                'label_html' => true,
                'class' => Role::class,
                'choice_label' => function($choice, $key, $value) {
                    return Role::getLabel($choice);
                },
                'required' => true,
            ])
            ->add('job', EntityType::class, [
                'label' => 'Quel métier ?',
                'class' => Job::class,
                'choice_label' => function ($job) {
                    return html_entity_decode($job->getName());
                },
                'query_builder' => function (EntityRepository $er)  use ($options) {
                    return $er->createQueryBuilder('j')
                        ->join('j.products', 'p')
                        ->andWhere('p = :product')
                        ->setParameter('product', $options['product'])
                        ->orderBy('j.name', 'ASC');
                },
                'required' => false,
                'placeholder' => '',
            ])
            ->add('triggers', CollectionType::class, [
                'label' => false,
                'entry_type' => WorkflowTriggerType::class,
                'entry_options' => [
                    'add_children' => true,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('step', HiddenType::class)
            ->add('actionId', HiddenType::class, [
                'mapped' => false,
            ])
        ;

        $builder->get('step')
            ->addModelTransformer($this->stepToNumberTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkflowAction::class,
            'product' => null,
        ]);
    }
}
