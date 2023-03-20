<?php

namespace App\EventSubscriber;

use App\Entity\Campaign;
use App\Entity\ChatNotification;
use App\Entity\Message;
use App\Entity\NotificationToSend;
use App\Entity\SystemEmail;
use App\Enum\FreqNotification;
use App\Enum\Manager;
use App\Enum\Notification;
use App\Enum\NotificationType;
use App\Enum\Role;
use App\Event\Campaign\CampaignCancelledEvent;
use App\Event\Campaign\CampaignCreatedEvent;
use App\Event\Campaign\CampaignEvaluationEvent;
use App\Event\Campaign\CampaignModifiedEvent;
use App\Event\Campaign\CampaignValidatedEvent;
use App\Event\Campaign\CampaignWaitingEvent;
use App\Event\Chat\MessageSentEvent;
use App\Repository\ChatNotificationRepository;
use App\Repository\NotificationToSendRepository;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use App\Enum\AdminMail;
use App\Event\Campaign\CampaignNotFinishedEvent;
use App\Event\Campaign\DevisCreatedNotFinished;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private MailerInterface $mailer,
        private ChatNotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
        private SystemEmailRepository $systemEmailRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private NotificationToSendRepository $notificationToSendRepository,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            CampaignValidatedEvent::NAME => 'onCampaignValidated',
            CampaignCancelledEvent::NAME => 'onCampaignCancelled',
            CampaignCreatedEvent::NAME => 'onCampaignCreated',
            CampaignModifiedEvent::NAME => 'onCampaignModified',
            CampaignWaitingEvent::NAME => 'onCampaignWaiting',
            CampaignEvaluationEvent::NAME => 'onCampaignEvaluation',
            MessageSentEvent::NAME => 'onMessageSent',
            CampaignNotFinishedEvent::NAME => 'onCampaignNotFinished',
            DevisCreatedNotFinished::NAME => 'onDevisCreatedNotFinished',
        ];
    }


    public function onDevisCreatedNotFinished(DevisCreatedNotFinished $event){
        $campaign = $event->getCampaign();
        $user = $event->getUser();
        $admins = AdminMail::cases();

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::DEMANDE_NON_TERMINER_CLIENT_DEVIS]);
        foreach ($admins as $admin) {
            $this->notificationService->create($email,$admin->value, $user, $user->getCompany(),null,$campaign);
        }
        return ;
    }

    public function onCampaignNotFinished(CampaignNotFinishedEvent $event){
        $campaign = $event->getCampaign();
        $mission = $event->getMission();
        $user = $event->getUser();
        $admins = AdminMail::cases();

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::DEMANDE_NON_TERMINER_CLIENT]);
        foreach ($admins as $admin) {
            $this->notificationService->create($email,$admin->value, $user, $user->getCompany(),null,$campaign);
        }
        return ;

    }

    public function onCampaignCreated(CampaignCreatedEvent $event)
    {
        $campaign = $event->getCampaign();
        $launchJustOne = true;
        if (!$campaign instanceof Campaign) {
            return;
        }
        $admins = AdminMail::cases();

        if ($campaign->getMissions()->count() === 0) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::DEMANDE_DEVIS_NOTIF_ADMIN]);
        } else {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CREATION_CAMPAGNE]);
        }

        if (null !== $email) {
            $files = (false !== $campaign->getMissions()->first()) ? $campaign->getMissions()->first()->getFileMissions() : null;
            $this->notificationService->create($email, 'operation@my-flow.fr', $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign, $files);

            foreach ($admins as $admin) {
                $this->notificationService->create($email, $admin->value, $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign, $files);
            }
        }

        $alreadySent = [];
        foreach ($campaign->getMissions() as $mission) {
            $count = 0;
            if ($campaign->getState() == 'in_progress') {
                $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CONFIRMER_MISSION]);
            } else {
                $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_DEMANDE_EVALUATION]);
            }

            foreach ($mission->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                    $count++;

                    if (null !== $email && !in_array($participant->getUser()->getEmail(), $alreadySent)) {
                        $this->notificationService->create($email, $participant->getUser(), $participant->getUser(), $mission->getCampaign()->getOrderedBy()->getCompany(), null, $campaign);
                        $alreadySent[] = $participant->getUser()->getEmail();
                    }
                }
            }
            if ($count === 0 && true === $launchJustOne) {
                $launchJustOne = false;
                $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_SANS_PARTENAIRE]);

                if (null !== $email) {
                    $this->notificationService->create($email, 'root_o53bxo3u@my-flow.com', $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign);

                    if (null !== $email->getSmsContent()) {
                        $admins = AdminMail::cases();

                        foreach ($admins as $admin) {
                            $this->notificationService->create($email, $admin->value, $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign, null, true);
                        }
                    }
                }
            }
        }

        if ($campaign->getMissions()->count() === 0) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::DEMANDE_DEVIS_NOTIF_CLIENT]);
        } elseif ($campaign->getState() == 'in_progress') {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CLIENT_CAMPAGNE_CREEE]);
        } else {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CLIENT_CAMPAGNE_A_EVALUER_CREEE]);
        }

        if (null !== $email) {
            $this->notificationService->create($email, $campaign->getOrderedBy(), $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign);
        }
    }

    public function onCampaignCancelled(CampaignCancelledEvent $event)
    {
        $campaign = $event->getCampaign();

        if (!$campaign instanceof Campaign) {
            return;
        }
        foreach ($campaign->getMissions() as $mission){
            $email = $mission->getContact()->getEmail();
            $notification = (new NotificationEmail())
                ->to(new Address($email))
                ->subject('Annulation campagne')
                ->content('
                <p>Bonjour,</p>
                <p>La campagne '. $campaign->getName() .' à été annulée pour la raison suivante: '. $campaign->getCancelReason() .'</p>
            ')
                ->markAsPublic()
            ;
            $this->mailer->send($notification);
        }
    }

    public function onCampaignModified(CampaignModifiedEvent $event)
    {
        $campaign = $event->getCampaign();

        if (!$campaign instanceof Campaign) {
            return;
        }

        $notification = (new NotificationEmail())
            ->to(new Address($campaign->getOrderedBy()->getEmail()))
            ->subject('Votre commande '.$campaign->getName().' a été modifiée')
            ->content('
                <p>Bonjour,</p>
                <p>Votre commande "'. $campaign->getName() .'" vient d\'être modifée.</p>
                <p>L\'équipe MyFlow</p>
            ')
            ->action('Accéder au tableau de bord', $this->router->generate('mission_index', [], UrlGeneratorInterface::ABSOLUTE_URL))
            ->markAsPublic()
        ;
        $this->mailer->send($notification);
    }

    public function onCampaignWaiting(CampaignWaitingEvent $event)
    {
        $campaign = $event->getCampaign();

        if (!$campaign instanceof Campaign) {
            return;
        }

        if ($event->getSystemEmail()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_EN_PAUSE]);

            //email envoyé aux sous-traitants et à celui qui la commandée
            if (null !== $email) {
                foreach ($campaign->getMissions() as $mission){
                    foreach ($mission->getParticipants() as $participant){
                        if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR || $participant->getRole() === Role::ROLE_VALIDATOR || $participant->getRole() === Role::ROLE_OBSERVER){
                            $this->notificationService->create($email, $participant->getUser(), $participant->getUser(), $campaign->getCompany(), null, $campaign);
                        }
                    }
                }
            }
        } else {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::RESOUMISSION_PANIER]);

            if (null !== $email) {
                $this->notificationService->create($email, $campaign->getOrderedBy(), $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany(), null, $campaign);
            }
        }
    }

    public function onCampaignEvaluation(CampaignEvaluationEvent $event)
    {
        $campaign = $event->getCampaign();

        if (!$campaign instanceof Campaign) {
            return;
        }

        $alreadySent = [];
        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_DEMANDE_EVALUATION]);

        if (null !== $email) {
            foreach ($campaign->getMissions() as $mission){
                foreach ($mission->getParticipants() as $participant){
                    if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR && !in_array($participant->getUser()->getEmail(), $alreadySent)){
                        $this->notificationService->create($email, $participant->getUser());
                        $alreadySent[] = $participant->getUser()->getEmail();
                    }
                }
            }
        }
    }

    public function onMessageSent(MessageSentEvent $event)
    {
        $message = $event->getMessage();

        if (!$message instanceof Message) {
            return;
        }

        foreach ($message->getCampaign()->getMissions() as $mission) {
            foreach ($mission->getParticipants() as $participant) {
                if (
                    $participant->getUser()->getId() != $message->getUser()->getId()
                    && in_array(NotificationType::EMAIL->value, $participant->getUser()->getNotificationType() ?? [])
                    && in_array(Notification::NEW_COMMENT_IN_CHAT->value, $participant->getUser()->getNotification() ?? [])
                    && !$this->notificationRepository->checkIfNotificationAwaiting($participant->getUser(), $mission->getCampaign())
                    && !$this->notificationToSendRepository->checkIfNotificationAwaiting($participant->getUser())
                ) {
                    if (FreqNotification::ALL_NOTIFICATION->value === $participant->getUser()->getFreqNotification()) {
                        $notification = (new ChatNotification())
                            ->setSendAt((new \DateTimeImmutable())->add(new \DateInterval('PT30M')))
                            ->setSendTo($participant->getUser())
                            ->setCampaign($message->getCampaign());

                        $this->entityManager->persist($notification);
                    } elseif (null !== $participant->getUser()->getFreqNotification()) {
                        if ($participant->getUser()->getFreqNotification() === FreqNotification::ONE_PER_DAY->value) {
                            if (date('Hi') > 1730) {
                                $interval = 1;
                            } else {
                                $interval = 0;
                            }
                        } else {
                            $interval = 5 - date('N');

                            if ($interval < 0) {
                                $interval = 7 - abs($interval);
                            }
                        }

                        $notification = (new NotificationToSend())
                            ->setSendAt((new \DateTimeImmutable())->add(new \DateInterval('P'.$interval.'D'))->setTime(17,30))
                            ->setSendTo($participant->getUser())
                            ->setType($participant->getUser()->getFreqNotification() === FreqNotification::ONE_PER_DAY->value ? NotificationToSend::DAILY : NotificationToSend::WEEKLY)
                        ;

                        $this->entityManager->persist($notification);
                    }
                } elseif (
                    $participant->getUser()->getId() !== $message->getUser()->getId()
                    && FreqNotification::ALL_NOTIFICATION->value === $participant->getUser()->getFreqNotification()
                    && in_array(NotificationType::EMAIL->value, $participant->getUser()->getNotificationType() ?? [])
                    && !empty($mission->getWorkflow()->getActiveStep())
                    && in_array(Notification::NEW_COMMENT_IN_CHAT_FOR_STEP->value, $participant->getUser()->getNotification() ?? [])
                    && (
                        ($participant->getRole() === Role::ROLE_SUBCONTRACTOR && $mission->getWorkflow()->getActiveStep()->getManager() === Manager::JOB)
                        || (($participant->getRole() === Role::ROLE_VALIDATOR || $participant->getRole() === Role::ROLE_OBSERVER) && $mission->getWorkflow()->getActiveStep()->getManager() === Manager::CLIENT)
                    )
                    && !$this->notificationRepository->checkIfNotificationAwaiting($participant->getUser(), $mission->getCampaign())
                    && !$this->notificationToSendRepository->checkIfNotificationAwaiting($participant->getUser())
                ) {
                    if (FreqNotification::ALL_NOTIFICATION->value === $participant->getUser()->getFreqNotification()) {
                        $notification = (new ChatNotification())
                            ->setSendAt((new \DateTimeImmutable())->add(new \DateInterval('PT30M')))
                            ->setSendTo($participant->getUser())
                            ->setCampaign($message->getCampaign());

                        $this->entityManager->persist($notification);
                    } elseif (null !== $participant->getUser()->getFreqNotification()) {
                        if ($participant->getUser()->getFreqNotification() === FreqNotification::ONE_PER_DAY->value) {
                            if (date('Hi') > 1730) {
                                $interval = 1;
                            } else {
                                $interval = 0;
                            }
                        } else {
                            $interval = 5 - date('N');

                            if ($interval < 0) {
                                $interval = 7 - abs($interval);
                            }
                        }

                        $notification = (new NotificationToSend())
                            ->setSendAt((new \DateTimeImmutable())->add(new \DateInterval('P'.$interval.'D'))->setTime(17,30))
                            ->setSendTo($participant->getUser())
                            ->setType($participant->getUser()->getFreqNotification() === FreqNotification::ONE_PER_DAY->value ? NotificationToSend::DAILY : NotificationToSend::WEEKLY)
                        ;

                        $this->entityManager->persist($notification);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }

    public function onCampaignValidated(CampaignValidatedEvent $event)
    {
        $campaign = $event->getCampaign();

        if (!$campaign instanceof Campaign) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::NOTIFICATION_VALIDATION_RECAP_CAMPAGNE]);
        $admins = AdminMail::cases();

        foreach ($admins as $admin) {
            $this->notificationService->create($email, $admin->value, $campaign->getOrderedBy(), $campaign->getOrderedBy()->getCompany());
        }
    }
}
