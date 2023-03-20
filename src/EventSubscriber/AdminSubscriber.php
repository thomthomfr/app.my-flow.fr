<?php

namespace App\EventSubscriber;

use App\Entity\Mission;
use App\Entity\SystemEmail;
use App\Enum\Role;
use App\Event\Admin\MissionInitialTimeAfterValidationUpdatedEvent;
use App\Event\Admin\MissionNotificationActivatedEvent;
use App\Event\Admin\MissionNotificationSubContractorNotActivatedEvent;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Event\AdminNotifiedEvent;
use App\Event\AdminNotifiedSubContractorCompletedEvent;
use App\Event\AdminNotifiedSubContractorNoResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Enum\AdminMail;

class AdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private SystemEmailRepository $systemEmailRepository,
        private NotificationService $notificationService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            MissionNotificationActivatedEvent::NAME => 'onMissionNotificationActivated',
            MissionNotificationSubContractorNotActivatedEvent::NAME => 'onMissionNotificationSubContractorNotActivated',
            AdminNotifiedEvent::NAME => 'onAdminNotified',
            AdminNotifiedSubContractorCompletedEvent::NAME => 'onSubContractorProfilComplete',
            AdminNotifiedSubContractorNoResponseEvent::NAME => 'onSubContractorNoResponse48H',
            MissionInitialTimeAfterValidationUpdatedEvent::NAME => 'onMissionInitialTimeAfterValidationUpdated',
        ];
    }

    public function onMissionNotificationActivated(MissionNotificationActivatedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $admins = AdminMail::cases();
        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_SOUS_TRAITANT_TOUS_ACTIVER]);

        if (null !== $email) {
            foreach ($admins as $admin) {
                $this->notificationService->create($email, $admin->value, $mission->getCampaign()->getOrderedBy(), $mission->getCampaign()->getOrderedBy()->getCompany());
            }
        }
    }

    public function onMissionNotificationSubContractorNotActivated(MissionNotificationSubContractorNotActivatedEvent $event)
    {
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }

        $admins = AdminMail::cases();
        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_SOUS_TRAITANT_PAS_ACTIVER_24H]);

        if (null !== $email) {
            foreach ($admins as $admin) {
                $this->notificationService->create($email, $admin->value);
            }
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISSION_RELANCE_ACTIVATION_SOUS_TRAITANT]);

        if (null !== $email) {
            $this->notificationService->create($email, $event->getUser());
        }
    }

    public function onSubContractorProfilComplete(AdminNotifiedSubContractorCompletedEvent $event)
    {
        $emailSystem = null;
        if ($event->getSubcontractor()) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::SOUS_TRAITANT_PROFIL_COMPLETE]);
        }
        $role = Role::ROLE_ADMIN->value;
        $admins = AdminMail::cases();

        if ($emailSystem !== null) {
            foreach ($admins as $admin) {
                $this->notificationService->create($emailSystem, $admin->value, null, null, null, null, null, true);
            }
        }
    }

    public function onSubContractorNoResponse48H(AdminNotifiedSubContractorNoResponseEvent $event)
    {
        $emailSystem = null;
        if ($event->getSubcontractor()) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::SOUS_TRAITANT_NO_REPONSE_48H]);
        }

        $role = Role::ROLE_ADMIN->value;
        $admins = AdminMail::cases();

        if ($emailSystem !== null) {
            foreach ($admins as $admin) {
                $this->notificationService->create($emailSystem, $admin->value, $event->getSubcontractor(), null, null, null, null, true, true);
            }
        }
    }

    public function onAdminNotified(AdminNotifiedEvent $event)
    {
        $emailSystem = null;
        if ($event->getSubcontractor()) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::NOTIFICATION_ADMIN]);
        }
        $role = Role::ROLE_ADMIN->value;
        $admins = AdminMail::cases();

        if ($emailSystem !== null) {
            foreach ($admins as $admin) {
                $this->notificationService->create($emailSystem, $admin->value);
            }
        }
    }

    public function onMissionInitialTimeAfterValidationUpdated(MissionInitialTimeAfterValidationUpdatedEvent $event)
    {
        $emailSystem = null;
        if ($event->getMission() != null) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::SOUS_TRAITANT_FORFAIT_INITIAL_TIME_UPDATED_AFTER_VALIDATION]);
        }

        $admins = AdminMail::cases();

        if ($emailSystem !== null) {
            foreach ($admins as $admin) {
                $this->notificationService->create($emailSystem, $admin->value);
            }
        }
    }
}
