<?php

namespace App\EventSubscriber;

use App\Entity\SystemEmail;
use App\Entity\User;
use App\Event\ClientUpdatedEvent;
use App\Repository\SystemEmailRepository;
use App\Repository\MissionParticipantRepository;
use App\Service\FrontAPIService;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Enum\Role;

class ClientSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FrontAPIService $frontAPIService,
        private SystemEmailRepository $systemEmailRepository,
        private MissionParticipantRepository $missionParticipantRepository,
        private NotificationService $notificationService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            ClientUpdatedEvent::NAME => 'onClientUpdated',
        ];
    }

    public function onClientUpdated(ClientUpdatedEvent $event)
    {
        $client = $event->getClient();

        if (!$client instanceof User) {
            return;
        }

        if ($event->getSendToApi()) {
            $this->frontAPIService->pushClientToFront($client, $event->getPlainPassword());
        }

        $email = null;
        if ($event->getSendNotification()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CREATION_NOUVEAU_CLIENT]);
        }

        if ($event->getThankYouNotification()) {
            $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::CONFIRMATION_INSCRIPTION]);
            $this->sendMailAddMission($client);
        }

        if (null !== $email) {
            $this->notificationService->create($email, $client, $client);
        }
    }

    /**
     * send email to validator or observator to invite to participate to the mission after confirmation inscription
     * @param  $user User
     * @return void
     */
    public function sendMailAddMission($user){
        $attendees = $this->missionParticipantRepository->getMissionForUser($user);
        foreach ($attendees as $key => $attendee) {
            $user = $attendee->getUser();
            $mission = $attendee->getMission();
            $company = $mission->getCampaign()->getCompany();
            if ($attendee->getRole() == Role::ROLE_VALIDATOR){
                $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_VALIDATEUR]);
            }else{
                $email = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::AJOUT_OBSERVATEUR]);
            }

            if (null !== $email) {
                $this->notificationService->create($email, $user, $user, $company, $mission->getWorkflow()?->getActiveStep(), $mission->getCampaign());
            }
        }
    }
}
