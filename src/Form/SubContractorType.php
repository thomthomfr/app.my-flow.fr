<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\User;
use App\Enum\BillingMethod;
use App\Enum\FreqNotification;
use App\Enum\Notification;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubContractorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom:'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom:'
            ])
            ->add('cellPhone', TextType::class, [
                'label' => 'Téléphone:',
                'attr' => [
                    'pattern' => '^(?:\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$',
                ],
            ])
            ->add('dailyRate', TextType::class, [
                'label' => 'Tarif Journalier:',
            ])
            ->add('picture', VichImageType::class, [
                'required' => false,
                'label' => 'Photo:',
                'attr' => [
                    'accept' => '.png, .jpg, .jpeg',
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'required' => true,
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => 'Monsieur',
                    'Madame' => 'Madame',
                ],
                'placeholder' => 'Choisissez',
            ])
            ->add('email', TextType::class, [
                'label' => 'Email:'
            ])
            ->add('notificationType', ChoiceType::class, [
                'label' => 'Comment acceptez vous d\'être contacté',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Email' => NotificationType::EMAIL->value,
                    'SMS' => NotificationType::SMS->value,
                    'Mobile' => NotificationType::MOBILE->value,
                ],
            ])
            ->add('notification', ChoiceType::class, [
                'label' => 'Personnalisez les notifications qui vous concernent et leur fréquence',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Un nouveau commentaire vient d\'être déposé sur le chat' => Notification::NEW_COMMENT_IN_CHAT->value,
                    'Un nouveau commentaire vient d\'être déposé sur le chat pour une étape qui me concerne' => Notification::NEW_COMMENT_IN_CHAT_FOR_STEP->value,
                    'Un état a été mis à jour' => Notification::MAJ_STATE->value,
                    'Solde de consommations' => Notification::CONSUMPTION_BALANCE->value,
                    'Une nouvelle facture est disponible' => Notification::NEW_BILL->value,
                    'Newsletter' => Notification::NEWSLETTER->value,
                ],
            ])
            ->add('freqNotification', ChoiceType::class, [
                'label' => 'Fréquence des notifications',
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Recevoir toutes les notifications' => FreqNotification::ALL_NOTIFICATION->value,
                    'Une notification par jour max' => FreqNotification::ONE_PER_DAY->value,
                    'Une notification par semaine max' => FreqNotification::ONE_PER_WEEK->value,
                ],
                'placeholder' => 'Choisissez',
            ]);
        ;

        if (!$options['admin']){
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'label' => 'Mot de passe',
                    'required' => false,
                ]);
        }

        if ($options['admin']) {
            $builder
                ->add('jobs',EntityType::class, [
                    'label' => 'Métiers:',
                    'class' => Job::class,
                    'multiple' => true,
                    'required' => true,
                    'by_reference' => false,
                ])
                ->add('billingMethod', ChoiceType::class, [
                    'label' => 'Mode de facturation',
                    'choices' => [
                        'Facturation au temps passé' => BillingMethod::BILL_TIME_PAST->value,
                        'Facturation à la prestation' => BillingMethod::BILL_PRESTATION->value,
                    ],
                    'placeholder' => 'Choisissez',
                ])
                ->add('resaleRate', TextType::class, [
                    'label' => 'Tarif Revente:',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'admin' => false,
        ]);
    }
}
