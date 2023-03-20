<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\WorkflowStep;
use App\Enum\Manager;
use App\Repository\JobRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'étape',
                'required' => true,
            ])
            ->add('completionTime', NumberType::class, [
                'label' => 'Temps de réalisation',
                'required' => true,
            ])
            ->add('customerDescription', TextareaType::class, [
                'label' => 'Description pour le client',
                'required' => false,
            ])
            ->add('supplierDescription', TextareaType::class, [
                'label' => 'Description pour le sous-traitant',
                'required' => false,
            ])
            ->add('stepId', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('manager', ChoiceType::class, [
                'label' => 'Responsable',
                'required' => true,
                'choice_loader' => new CallbackChoiceLoader(function() {
                    $choices = [];
                    foreach (Manager::cases() as $type) {
                        $choices[Manager::getLabel($type)] = $type;
                    }
                    return $choices;
                }),
            ])
            ->add('job', EntityType::class, [
                'label' => 'Métier',
                'required' => false,
                'class' => Job::class,
                'choice_label' => function ($job) {
                    return html_entity_decode($job->getName());
                },
                'query_builder' => function (JobRepository $er) use ($builder) {
                    return $er->createQueryBuilder('j')
                        ->join('j.products', 'p')
                        ->andWhere('p = :product')
                        ->setParameter('product', $builder->getData()->getWorkflow()->getProduct())
                        ->orderBy('j.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkflowStep::class,
        ]);
    }
}
