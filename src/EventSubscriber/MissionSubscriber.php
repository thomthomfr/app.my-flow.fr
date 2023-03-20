<?php

namespace App\EventSubscriber;

use App\Entity\Mission;
use App\Entity\SystemEmail;
use App\Entity\User;
use App\Enum\Role;
use App\Enum\Trigger;
use App\Event\Mission\ContactAddedEvent;
use App\Event\Mission\MissionAcceptedEvent;
use App\Event\Mission\MissionActivatedEvent;
use App\Event\Mission\MissionArchivedEvent;
use App\Event\Mission\MissionCancelledEvent;
use App\Event\Mission\MissionDesiredDeliveryUpdatedAfterValidationEvent;
use App\Event\Mission\MissionDesiredDeliveryUpdatedBeforeValidationEvent;
use App\Event\Mission\MissionInitialTimeEvent;
use App\Event\Mission\MissionRealTimeEvent;
use App\Event\Mission\MissionRefusedEvent;
use App\Event\Mission\MissionWithoutSubContractorCheckedEvent;
use App\Event\Mission\MissionWithoutWorkflowEvent;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\TriggerService;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use App\Event\Mission\MissionSendEvent;
use App\Repository\MissionParticipantRepository;
use App\Enum\AdminMail;

class MissionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private RouterInterface $router,
        private TriggerService $triggerService,
        private SystemEmailRepository $systemEmailRepository,
        private NotificationService $notificationService,
        private MissionParticipantRepository $missionParticipantRepository,

    ){}

    public static function getSubscribedEvents()
    {
        return [
            MissionRefusedEvent::NAME => 'onMissionRefused',
            MissionArchivedEvent::NAME => 'onMissionArchived',
            MissionWithoutWorkflowEvent::NAME => 'onMissionWithoutWorkflow',
            MissionCancelledEvent::NAME => 'onMissionCancelled',
            MissionAcceptedEvent::NAME => 'onMissionAccepted',
            MissionInitialTimeEvent::NAME => 'onMissionInitialTime',
            MissionRealTimeEvent::NAME => 'onMissionRealTime',
            ContactAddedEvent::NAME => 'onContactAdded',
            MissionWithoutSubContractorCheckedEvent::NAME => 'onMissionWithoutSubContractorChecked',
            MissionActivatedEvent::NAME => 'onMissionActivated',
            MissionDesiredDeliveryUpdatedAfterValidationEvent::NAME => 'onMissionDesiredDeliveryUpdatedAfterValidation',
            MissionDesiredDeliveryUpdatedBeforeValidationEvent::NAME => 'onMissionDesiredDeliveryUpdatedBeforeValidation',
            MissionSendEvent::NAME => 'onMissionSend',
        ];
    }

    public function onMissionSend(MissionSendEvent $event){
        $user = $event->getUser();
        $mission = $event->getMission();
        $company = $mission->getCampaign()->getCompany();
        if (!$mission instanceof Mission) {
            return;
        }

        if (!$user instanceof User) {
            return;
        }

        if (trim($event->getRole()) == 'ROLE_VALIDATOR'){
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_VALIDATEUR]);
        }else{
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_OBSERVATEUR]);
        }
        if (null !== $email) {
            $this->notificationService->create($email, $user, $user, $company, $mission->getWorkflow()?->getActiveStep(), $mission->getCampaign());
        }
    }

    public function onMissionRefused(MissionRefusedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $intervenant = $event->getIntervenant();

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_REFUSEE_INTERVENANT]);

        if (null !== $email) {
            $this->notificationService->create($email, $intervenant, $intervenant);
        }

        $admins = AdminMail::cases();
        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_REFUSEE_ADMIN]);

        if (null !== $email) {
            foreach ($admins as $admin) {
                $this->notificationService->create($email, $admin->value, $intervenant);
            }
        }
    }

    public function onMissionArchived(MissionArchivedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        foreach ($mission->getWorkflow()->getSteps()->last()->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::MISSION_ARCHIVED) {
                    $this->triggerService->execute($trigger);
                }
            }
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_ARCHIVED_CLIENT]);

        if (null !== $email) {
            $this->notificationService->create($email, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_ARCHIVED_PRESTATAIRE]);

        if (null !== $email) {
            foreach ($mission->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                    $this->notificationService->create($email, $participant->getUser(), $participant->getUser(), $mission->getCampaign()->getOrderedBy()->getCompany());
                }
            }
        }

        $admins = AdminMail::cases();
        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_ARCHIVED_ADMIN]);

        if (null !== $email) {
            foreach ($admins as $admin){
                $this->notificationService->create($email, $admin->value, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
            }
        }
    }

    public function onMissionWithoutWorkflow(MissionWithoutWorkflowEvent $event)
    {
        
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $admins = AdminMail::cases();

        foreach ($admins as $admin) {
            $notification = (new NotificationEmail())
                ->to(new Address($admin->value))
                ->subject('Une mission a été créée sans workflow')
                ->content('
                <p>Bonjour,</p>
                <p>La mission '. $mission->getReference() .' contient un produit "'.$mission->getProduct()->getName().'" qui n\'a pas de Workflow associé.</p>
                <p>Merci d\'en créer un pour ce produit et d\'aller le relier à la mission.</p>
            ')
                ->action('Modifier la mission', $this->router->generate('handle_mission_campaign', ['id' => $mission->getCampaign()->getId()], UrlGeneratorInterface::ABSOLUTE_URL))
                ->markAsPublic()
            ;
            $this->mailer->send($notification);
        }
    }

    public function onMissionDesiredDeliveryUpdatedAfterValidation(MissionDesiredDeliveryUpdatedAfterValidationEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_DESIRED_DELIVERY_UPDATED_AFTER_VALIDATION]);

        if (null !== $email) {
            $this->notificationService->create($email, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
        }
    }

    public function onMissionDesiredDeliveryUpdatedBeforeValidation(MissionDesiredDeliveryUpdatedBeforeValidationEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_DESIRED_DELIVERY_UPDATED_BEFORE_VALIDATION]);

        if (null !== $email) {
            $this->notificationService->create($email, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
        }
    }

    public function onMissionCancelled(MissionCancelledEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_CANCELLED]);

        if (null !== $email) {
            $this->notificationService->create($email, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
        }
    }

    public function onMissionAccepted(MissionAcceptedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $intervenant = $event->getIntervenant();

        $notification = (new NotificationEmail())
            ->to(new Address($intervenant->getEmail()))
            ->subject('La mission a bien été acceptée')
            ->content('
                <p>Bonjour,</p>
                <p>Votre choix d\'accepter la mission <a href="'.$this->router->generate('mission_edit', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'">'. $mission->getCampaign()->getName() .'</a> a bien été pris en compte.</p>
            ')
            ->action('Commencer la mission !', $this->router->generate('mission_edit', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL))
            ->markAsPublic()
        ;
        $this->mailer->send($notification);

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_ACCEPTEE_CLIENT]);

        if (null !== $email) {
            $this->notificationService->create($email, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
        }
    }

    public function onMissionInitialTime(MissionInitialTimeEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }
        $admins = AdminMail::cases();

        foreach ($admins as $admin) {
            $notification = (new NotificationEmail())
                ->to(new Address($admin->value))
                ->subject('Un intervenant vient d\'ajouter le temps initial d\'une mission')
                ->content('
                    <p>Bonjour,</p>
                    <p>Le temps initial pour la mission '. $mission->getCampaign()->getName() .' vient d\'être ajouté</p>
                ')
                ->action('Voir la mission', $this->router->generate('mission_edit', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL))
                ->markAsPublic()
            ;

            try {
                $this->mailer->send($notification);
            } catch (\Exception $e) { /* TODO: logger ou afficher une alerte que l'email n'a pas été envoyé */ }
        }
    }

    public function onMissionRealTime(MissionRealTimeEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::RENSEIGNEZ_TEMPS_MISSION]);

        if (null !== $emailSystem) {
            foreach ($mission->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                    $this->notificationService->create($emailSystem, $participant->getUser(), $participant->getUser(), $mission->getCampaign()->getOrderedBy()->getCompany(), null, $mission->getCampaign());
                }
            }
        }
    }

    public function onContactAdded(ContactAddedEvent $event)
    {
        $contact = $event->getContact();

        if (!$contact instanceof User) {
            return;
        }
        if ($event->getParticipant()->getRole() == Role::ROLE_VALIDATOR){
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_VALIDATEUR]);
        }else{
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_OBSERVATEUR]);
        }

        if (null !== $email) {
            $this->notificationService->create($email, $contact, $contact, $contact->getCompany(), $event->getMission()->getWorkflow()?->getActiveStep(), $event->getMission()->getCampaign());
        }
    }

    public function onMissionActivated(MissionActivatedEvent $event)
    {
        $mission = $event->getMission();
        $client = $event->getClient();

        if (!$mission instanceof Mission) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_ACTIVER_CLIENT]);

        if (null !== $email) {
            $this->notificationService->create($email, $client, $client, $client->getCompany(), $mission->getWorkflow()?->getSteps()?->first());
        }
    }

    public function onMissionWithoutSubContractorChecked(MissionWithoutSubContractorCheckedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_SANS_PARTENAIRE]);

        if (null !== $email) {
            $this->notificationService->create($email, 'operation@my-flow.fr', $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());

            if (null !== $email->getSmsContent()) {
                $admins = AdminMail::cases();

                foreach ($admins as $admin) {
                    $this->notificationService->create($email, $admin->value, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany(), null, null, null, true);
                }
            }
        }
    }
}
