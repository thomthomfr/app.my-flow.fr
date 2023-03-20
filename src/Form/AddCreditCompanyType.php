<?php

namespace App\Form;

use App\Entity\CreditHistory;
use App\Enum\TypePack;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddCreditCompanyType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('credit', TextType::class, [
                'label' => false,
            ])
            ->add('mensualite', TextType::class, [
                'label' => false,
            ])
            ->add('annuite', TextType::class, [
                'label' => false,
            ])
            ->add('report', TextType::class, [
                'label' => 'Report',
            ])
            ->add('typePack', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'choices' => [
                    'Crédit' => TypePack::CREDIT->value,
                    'Forfait mensuel' => TypePack::FORFAIT_MENSUEL->value,
                    'Forfait annuel' => TypePack::FORFAIT_ANNUEL->value,
                ],
            ])
            ->add('startDateContract', DateTimeType::class, [
                'label' => 'Date de début du contrat',
                'widget' => 'single_text'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditHistory::class,
        ]);
    }
}
