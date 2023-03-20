<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WorkflowTrigger;
use App\Enum\FreqNotification;
use App\Enum\Manager;
use App\Enum\NotificationType;
use App\Enum\Operation;
use App\Enum\Role;
use App\Event\Workflow\Step\WorkflowStepEnteredEvent;
use App\Event\Workflow\Step\WorkflowStepExitedEvent;
use App\Event\Workflow\Step\WorkflowStepReturnedEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Enum\AdminMail;

class TriggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private EventDispatcherInterface $dispatcher,
        private ShortcodeService $shortcodeService,
        private Messaging $messaging,
    ){}

    public function execute(WorkflowTrigger $trigger): void
    {
        match($trigger->getOperation()) {
            Operation::NEXT_STEP => $this->move($trigger, 1),
            Operation::PREVIOUS_STEP => $this->move($trigger, -1),
            Operation::EMAIL => $this->sendEmail($trigger),
            Operation::ADMIN_ALERT => $this->sendAdminAlert($trigger),
            Operation::PROJECT_MANAGER_ALERT => $this->sendProjectManagerAlert($trigger),
            Operation::COMMERCIAL_ALERT => $this->sendCommercialAlert($trigger),
        };
    }

    private function move(WorkflowTrigger $trigger, int $number): void
    {
        $step = $trigger->getAction()->getStep();
        $step->setActive(false);
        $step->setEndDate(new \DateTime());

        if (null !== $trigger->getEmailTemplate()) {
            $this->sendEmail($trigger);
        }

        $event = new WorkflowStepExitedEvent($step);
        $this->dispatcher->dispatch($event, WorkflowStepExitedEvent::NAME);

        $workflow = $step->getWorkflow();
        $steps = $workflow->getSteps();

        $currentIndex = $steps->indexOf($step);
        $nextStep = $steps->get($currentIndex + $number);

        if (null !== $nextStep) {
            if (null === $nextStep->getStartDate()) {
                $event = new WorkflowStepEnteredEvent($nextStep);
                $this->dispatcher->dispatch($event, WorkflowStepEnteredEvent::NAME);
            } else {
                $event = new WorkflowStepReturnedEvent($nextStep);
                $this->dispatcher->dispatch($event, WorkflowStepReturnedEvent::NAME);
            }

            $nextStep->setActive(true);
            $nextStep->setStartDate(new \DateTime());
            $nextStep->setEndDate(null);

            if ($nextStep->getManager() === Manager::CLIENT) {
                $workflow->getMission()->setStateClient($nextStep->getName());
                $workflow->getMission()->setStateProvider(null);
            } else {
                $workflow->getMission()->setStateProvider($nextStep->getName());
                $workflow->getMission()->setStateClient(null);
            }
        }

        $this->entityManager->flush();
    }

    private function sendEmail(WorkflowTrigger $trigger): void
    {
        $to = [];

        if ($trigger->getAction()->getRecipient() === Role::ROLE_CLIENT) {
            foreach ($trigger->getAction()->getStep()->getWorkflow()->getMission()->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_VALIDATOR || $participant->getRole() === Role::ROLE_OBSERVER) {
                    $to[] = $participant->getUser();
                }
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_ADMIN) {
            $admins = AdminMail::cases();
            foreach ($admins as $admin) {
                $to[] = $admin->value;
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_VALIDATOR) {
            foreach ($trigger->getAction()->getStep()->getWorkflow()->getMission()->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_VALIDATOR) {
                    $to[] = $participant->getUser();
                }
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_OBSERVER) {
            foreach ($trigger->getAction()->getStep()->getWorkflow()->getMission()->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_OBSERVER) {
                    $to[] = $participant->getUser();
                }
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_SUBCONTRACTOR) {
            foreach ($trigger->getAction()->getStep()->getWorkflow()->getMission()->getParticipants() as $participant) {
                if (null !== $trigger->getAction()->getJob()) {
                    if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR && $participant->getJob() === $trigger->getAction()->getJob()) {
                        $to[] = $participant->getUser();
                    }
                } else {
                    if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                        $to[] = $participant->getUser();
                    }
                }
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_ALL) {
            foreach ($trigger->getAction()->getStep()->getWorkflow()->getMission()->getParticipants() as $participant) {
                $to[] = $participant->getUser();
            }
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_COMMERCIAL) {
            $to[] = 'commerce@my-flow.fr';
        } elseif ($trigger->getAction()->getRecipient() === Role::ROLE_PROJECT_MANAGER) {
            $to[] = 'operation@my-flow.fr';
        }

        $this->sendNotification($to, $trigger);
    }

    private function sendAdminAlert(WorkflowTrigger $trigger): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
        $this->sendNotification($admins, $trigger);
    }

    private function sendProjectManagerAlert(WorkflowTrigger $trigger): void
    {
        $this->sendNotification(['operations@my-flow.fr'], $trigger);
    }

    private function sendCommercialAlert(WorkflowTrigger $trigger): void
    {
        $this->sendNotification(['commerce@my-flow.fr'], $trigger);
    }

    private function sendNotification(array $to, WorkflowTrigger $trigger): void
    {
        if (null !== $trigger->getEmailTemplate()) {
            foreach ($to as $user) {
                if ($user instanceof User && in_array(NotificationType::EMAIL->value, $user->getNotificationType()) && FreqNotification::ALL_NOTIFICATION->value === $user->getFreqNotification()) {
                    try {
                        $email = $user instanceof User ? new Address($user->getEmail()) : $user;
                        $content = $trigger->getEmailTemplate()->getContent();

                        $notification = (new NotificationEmail())
                            ->from(new Address('no-reply@my-flow.fr', $trigger->getEmailTemplate()->getSenderName()))
                            ->to($email)
                            ->subject($this->shortcodeService->parse($trigger->getEmailTemplate()->getSubject(), $user, $trigger->getAction()->getStep()->getWorkflow()->getMission()->getCampaign()->getCompany(), $trigger->getAction()->getStep()))
                            ->content($this->shortcodeService->parse($content, $user, $trigger->getAction()->getStep()->getWorkflow()->getMission()->getCampaign()->getCompany(), $trigger->getAction()->getStep()))
                            ->replyTo('operation@my-flow.fr')
                            ->context(['replyTo' => true, 'campaignId' => $trigger->getAction()->getStep()->getWorkflow()->getMission()->getCampaign()->getId()])
                            ->markAsPublic()
                        ;

                        $this->mailer->send($notification);
                    } catch (\Exception $e) {}
                }
            }

            if (!empty($trigger->getEmailTemplate()->getPushContent()) && $user instanceof User && in_array(NotificationType::MOBILE->value, $user->getNotificationType()) && FreqNotification::ALL_NOTIFICATION->value === $user->getFreqNotification()) {
                try {
                    foreach ($user->getDevices() as $device) {
                        $notification = CloudMessage::withTarget('token', $device->getDeviceid())
                            ->withNotification(Notification::create($trigger->getEmailTemplate()->getSubject(), $trigger->getEmailTemplate()->getPushContent()))
                            ->withDefaultSounds()
                        ;

                        $this->messaging->send($notification);
                    }
                } catch (\Exception $e) {}
            }
        }
    }
}
