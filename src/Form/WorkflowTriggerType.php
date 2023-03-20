<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use App\Entity\WorkflowTrigger;
use App\Enum\Operation;
use App\Enum\Operator;
use App\Enum\Period;
use App\Enum\Trigger;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowTriggerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('triggerType', EnumType::class, [
                'label' => 'Déclencheur',
                'class' => Trigger::class,
                'required' => true,
                'choice_label' => function($choice, $key, $value) {
                    return Trigger::getLabel($choice);
                }
            ])
            ->add('operator', EnumType::class, [
                'label' => 'Type',
                'required' => false,
                'placeholder' => '',
                'class' => Operator::class,
                'choice_label' => function($choice, $key, $value) {
                    return Operator::getLabel($choice);
                },
            ])
            ->add('emailTemplate', EntityType::class, [
                'label' => 'Titre',
                'class' => EmailTemplate::class,
                'choice_label' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.title', 'ASC');
                },
                'required' => false,
                'placeholder' => '',
            ])
            ->add('timePeriod', EnumType::class, [
                'label' => 'Délais',
                'required' => false,
                'placeholder' => '',
                'class' => Period::class,
                'choice_label' => function($choice, $key, $value) {
                    return Period::getLabel($choice);
                }
            ])
            ->add('operation', EnumType::class, [
                'label' => 'Action',
                'required' => true,
                'class' => Operation::class,
                'choice_label' => function($choice, $key, $value) {
                    return Operation::getLabel($choice);
                }
            ])
        ;

        if ($options['add_children']) {
            $builder
                ->add('childs', CollectionType::class, [
                    'label' => false,
                    'entry_type' => WorkflowTriggerType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype_name' => '__child__',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkflowTrigger::class,
            'add_children' => false,
        ]);
    }
}
