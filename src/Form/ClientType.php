<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\FreqNotification;
use App\Enum\NotificationType;
use App\Enum\Notification;
use App\Enum\Role;
use App\Form\DataTransformer\NotificationToEnumTransformer;
use App\Form\DataTransformer\NotificationTypeToEnumTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{

    public function __construct(
        private NotificationTypeToEnumTransformer $notificationTypeToEnumTransformer,
        private NotificationToEnumTransformer $notificationToEnumTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('cellPhone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'pattern' => '^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$',
                ],
                'help' => 'Veuillez renseigner le format international "+33" suivi des 9 chiffres de votre numéro de téléphone.',
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse email'
            ])
            ->add('roles', ChoiceType::class, [
                'required' => true,
                'label' => 'Rôle du client',
                'multiple' => false,
                'expanded' => false,
                'choices'  => [
                    'Client' => Role::ROLE_CLIENT->value,
                    'Client administrateur' => Role::ROLE_CLIENT_ADMIN->value,
                ],
            ])
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'label' => 'Entreprise associée',
                'choice_label' => 'name',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Civilité',
                'expanded' => false,
                'required' => true,
                'multiple' => false,
                'choices' => [
                    'Monsieur' => 'Monsieur',
                    'Madame' => 'Madame',
                ],
                'placeholder' => 'Choisissez',
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
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return count($rolesArray)? $rolesArray[0]: null;
                },
                function ($rolesString) {
                    // transform the string back to an array
                    return [$rolesString];
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
