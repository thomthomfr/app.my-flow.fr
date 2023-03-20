<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\FreqNotification;
use App\Enum\NotificationType;
use App\Enum\Notification;
use App\Form\DataTransformer\NotificationToEnumTransformer;
use App\Form\DataTransformer\NotificationTypeToEnumTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientProfilType extends AbstractType
{

    public function __construct(
        private NotificationTypeToEnumTransformer $notificationTypeToEnumTransformer,
        private NotificationToEnumTransformer $notificationToEnumTransformer,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'required' => true,
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => 'Monsieur',
                    'Madame' => 'Madame',
                ],
                'placeholder' => 'Choisissez',
            ])
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

        if (!$options['admin']){
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'label' => 'Mot de passe',
                    'required' => false,
                ]);
        }
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'admin' => false,
        ]);
    }
}
