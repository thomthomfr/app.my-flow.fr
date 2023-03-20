<?php

namespace App\EventSubscriber;

use App\Entity\SystemEmail;
use App\Entity\User;
use App\Event\SubContractor\SubContractorCompletedProfileEvent;
use App\Event\SubContractor\SubContractorMissionAddedEvent;
use App\Event\SubContractor\SubContractorReferencedEvent;
use App\Event\SubContractor\SubContractorServiceAddedEvent;
use App\Event\SubContractorRelaunchedEvent;
use App\Event\SubContractorUpdatedEvent;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\FrontAPIService;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Enum\AdminMail;

class SubContractorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FrontAPIService $frontAPIService,
        private SystemEmailRepository $systemEmailRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            SubContractorUpdatedEvent::NAME => 'onSubcontractorUpdated',
            SubContractorRelaunchedEvent::NAME => 'onSubcontractorRelaunched',
            SubContractorReferencedEvent::NAME => 'onSubContractorReferenced',
            SubContractorCompletedProfileEvent::NAME => 'onSubContractorCompletedProfile',
            SubContractorMissionAddedEvent::NAME => 'onSubContractorMissionAdded',
            SubContractorServiceAddedEvent::NAME => 'onSubContractorServiceAdded',
        ];
    }

    public function onSubcontractorUpdated(SubContractorUpdatedEvent $event)
    {
        $subcontractor = $event->getSubcontractor();

        if (!$subcontractor instanceof User) {
            return;
        }

        $this->frontAPIService->pushSubcontractorToFront($subcontractor);
        $admins = AdminMail::cases();

        if ($event->getSendNotification()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CREATION_NOUVEAU_SOUS_TRAITANT]);

            if (null !== $email) {
                $this->notificationService->create($email, $subcontractor, $subcontractor);
            }

            foreach ($admins as $admin) {
                $this->notificationService->create($email, $admin->value, $subcontractor, null, null, null, null, true);
            }
        }


        if ($event->getThankYouNotification()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CONFIRMATION_INSCRIPTION]);

            if (null !== $email) {
                $this->notificationService->create($email, $subcontractor, $subcontractor, null, null, null, null, true, true);
            }
        }

        if ($event->getUpdateProfileNotification()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::MISE_A_JOUR_PROFIL_ATTENDU]);

            if (null !== $email) {
                $this->notificationService->create($email, $subcontractor, $subcontractor);
            }
        }
    }

    public function onSubcontractorRelaunched(SubContractorRelaunchedEvent $event)
    {
        $emailSystem = null;
        if ($event->getSubcontractor()) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::RELANCE_SOUS_TRAITANT]);
        }
        if ($emailSystem !== null){
            $this->notificationService->create($emailSystem, $event->getSubcontractor(), $event->getSubcontractor());
        }
    }

    public function onSubContractorReferenced(SubContractorReferencedEvent $event)
    {
        $subcontractor = $event->getUser();

        if (!$subcontractor instanceof User) {
            return;
        }

        if (!$subcontractor->getReferencingConfirmationNotification()) {
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CONFIRMATION_REFERENCEMENT]);

            if ($emailSystem !== null){
                $this->notificationService->create($emailSystem, $subcontractor, $subcontractor);
            }
        }
    }

    public function onSubContractorCompletedProfile(SubContractorCompletedProfileEvent $event)
    {
        $subcontractor = $event->getUser();

        if (!$subcontractor instanceof User) {
            return;
        }

        $admins = AdminMail::cases();
        $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::SOUS_TRAITANT_PROFIL_COMPLETE]);

        if ($emailSystem !== null){
            foreach ($admins as $admin) {
                $this->notificationService->create($emailSystem, $admin->value, $subcontractor);
            }
        }
    }

    public function onSubContractorMissionAdded(SubContractorMissionAddedEvent $event)
    {
        $subcontractor = $event->getUser();
        $mission = $event->getMission();

        if (!$subcontractor instanceof User) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CONFIRMER_MISSION]);

        if ($email !== null){
            $this->notificationService->create($email, $subcontractor, $subcontractor, $mission->getCampaign()->getOrderedBy()->getCompany(), null, $mission->getCampaign());
        }
    }

    public function onSubContractorServiceAdded(SubContractorServiceAddedEvent $event)
    {
        $subcontractor = $event->getUser();

        if (!$subcontractor instanceof User) {
            return;
        }

        $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::SOUS_TRAITANT_ADD_SERVICE]);

        if ($email !== null){
            $this->notificationService->create($email, $subcontractor, $subcontractor);
        }
    }
}
