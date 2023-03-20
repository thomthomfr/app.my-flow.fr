<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Historique;
use App\Entity\User;
use App\Entity\WorkflowStep;
use App\Enum\Operation;
use App\Enum\Role;
use App\Event\Mission\MissionRealTimeEvent;
use App\Event\Workflow\Step\WorkflowStepRefusedEvent;
use App\Event\Workflow\Step\WorkflowStepValidatedEvent;
use App\Repository\MissionParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Security;

class WorkflowStepService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private MissionParticipantRepository $missionParticipantRepository,
    ){}

    public function validate(WorkflowStep $step, User $user): bool
    {
        $historique = (new Historique())
            ->setUser($user)
            ->setMission($step->getWorkflow()->getMission())
            ->setMessage($user.' a validé l\'étape '.$step->getName());

        $participantObj = null;

        $this->entityManager->persist($historique);
        $skipCheck = false;

        if ($this->security->isGranted('ROLE_ADMIN', $user)) {
            $this->entityManager->flush();
            $validate = true;
        } else {
            $participantObj = $this->missionParticipantRepository->findOneBy(['user' => $user, 'mission' => $step->getWorkflow()->getMission()]);

            if (null !== $participantObj) {
                if ($participantObj->getRole() === Role::ROLE_VALIDATOR) {
                    $participantObj->setValidStep(true);
                    $this->entityManager->persist($participantObj);
                } else {
                    foreach ($step->getActions() as $action) {
                        foreach ($action->getTriggers() as $trigger) {
                            if ($trigger->getOperation() == Operation::NEXT_STEP && $action->getRecipient() == $participantObj->getRole()) {
                                $participantObj->setValidStep(true);
                                $this->entityManager->persist($participantObj);
                                $skipCheck = true;
                                break;
                            }
                        }
                    }
                }
                $this->entityManager->flush();
            }

            $validate = true;
            if (!$skipCheck) {
                foreach ($step->getWorkflow()->getMission()->getParticipants() as $participant) {
                    if ($participant->getRole() === Role::ROLE_VALIDATOR && !$participant->getValidStep()) {
                        $validate = false;
                        break;
                    }
                }
            }
        }

        if ($validate) {
            $event = new WorkflowStepValidatedEvent($step);
            $this->dispatcher->dispatch($event, WorkflowStepValidatedEvent::NAME);
            foreach ($step->getWorkflow()->getMission()->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_VALIDATOR && !$participant->getValidStep()) {
                    $participant->setValidStep(false);
                    $this->entityManager->persist($participant);
                }
            }

            if ($participantObj !== null) {
                foreach ($step->getActions() as $action) {
                    foreach ($action->getTriggers() as $trigger) {
                        if ($trigger->getOperation() == Operation::NEXT_STEP && $action->getRecipient() == $participantObj->getRole()) {
                            $participantObj->setValidStep(true);
                            $this->entityManager->persist($participantObj);
                            break;
                        }
                    }
                }
            }

            if ($step->getWorkflow()->getSteps()->last() === $step) {
                $mission = $step->getWorkflow()->getMission();
                if ($mission->getCampaign()->getCompany()->getContract() == Company::END_OF_MONTH_BILLING || $mission->getCampaign()->getCompany()->getContract() == Company::MONTHLY_BILLING) {
                    $event = new MissionRealTimeEvent($mission);
                    $this->dispatcher->dispatch($event, MissionRealTimeEvent::NAME);
                }

                return true;
            }
        }

        return false;
    }

    public function requestChange(WorkflowStep $step, User $user): void
    {
        $event = new WorkflowStepRefusedEvent($step);
        $this->dispatcher->dispatch($event, WorkflowStepRefusedEvent::NAME);

        $historique = (new Historique())
            ->setUser($user)
            ->setMission($step->getWorkflow()->getMission())
            ->setMessage($user.' a demandé des modifications pour l\'étape '.$step->getName());
        $this->entityManager->persist($historique);
        $this->entityManager->flush();
    }
}
