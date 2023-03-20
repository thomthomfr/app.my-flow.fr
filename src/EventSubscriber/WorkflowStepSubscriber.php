<?php

namespace App\EventSubscriber;

use App\Entity\Workflow;
use App\Entity\WorkflowStep;
use App\Enum\Trigger;
use App\Event\Workflow\Step\WorkflowStepDeletedEvent;
use App\Event\Workflow\Step\WorkflowStepEditedEvent;
use App\Event\Workflow\Step\WorkflowStepEnteredEvent;
use App\Event\Workflow\Step\WorkflowStepExitedEvent;
use App\Event\Workflow\Step\WorkflowStepRefusedEvent;
use App\Event\Workflow\Step\WorkflowStepRelaunchedEvent;
use App\Event\Workflow\Step\WorkflowStepReturnedEvent;
use App\Event\Workflow\Step\WorkflowStepValidatedEvent;
use App\Service\FrontAPIService;
use App\Service\TriggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowStepSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TriggerService $triggerService,
        private FrontAPIService $frontAPIService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            WorkflowStepValidatedEvent::NAME => 'onStepValidated',
            WorkflowStepRefusedEvent::NAME => 'onStepRefused',
            WorkflowStepEnteredEvent::NAME => 'onStepEntered',
            WorkflowStepReturnedEvent::NAME => 'onStepReturned',
            WorkflowStepExitedEvent::NAME => 'onStepExited',
            WorkflowStepRelaunchedEvent::NAME => 'onStepRelaunched',
            WorkflowStepEditedEvent::NAME => 'onStepEdited',
            WorkflowStepDeletedEvent::NAME => 'onStepDeleted',
        ];
    }

    public function onStepValidated(WorkflowStepValidatedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
		    if ($trigger->getTriggerType() === Trigger::VALIDATION) {
			    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::VALIDATION) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepRefused(WorkflowStepRefusedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::REFUSAL) {
                    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::REFUSAL) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepEntered(WorkflowStepEnteredEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::ENTER_STEP) {
                    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::ENTER_STEP) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepReturned(WorkflowStepReturnedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::RETURN_TO_STEP) {
                    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::RETURN_TO_STEP) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepExited(WorkflowStepExitedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::EXIT_STEP) {
                    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::EXIT_STEP) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepRelaunched(WorkflowStepRelaunchedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        foreach ($step->getActions() as $action) {
            foreach ($action->getTriggers() as $trigger) {
                if ($trigger->getTriggerType() === Trigger::RELAUNCH_CLIENT) {
                    $this->triggerService->execute($trigger);
                }

                if ($trigger->getTriggerType() === Trigger::CONDITIONAL_OR) {
                    foreach ($trigger->getChilds() as $child) {
                        if ($child->getTriggerType() === Trigger::RELAUNCH_CLIENT) {
                            $this->triggerService->execute($child);
                        }
                    }
                }
            }
        }
    }

    public function onStepEdited(WorkflowStepEditedEvent $event)
    {
        $step = $event->getStep();

        if (!$step instanceof WorkflowStep) {
            return;
        }

        $this->frontAPIService->editProductDeliveryTime(
            product: $step->getWorkflow()->getProduct(),
            deliveryTime: $step->getWorkflow()->getTotalCompletionTime(),
        );
    }

    public function onStepDeleted(WorkflowStepDeletedEvent $event)
    {
        $workflow = $event->getWorkflow();

        if (!$workflow instanceof Workflow) {
            return;
        }

        $this->frontAPIService->editProductDeliveryTime(
            product: $workflow->getProduct(),
            deliveryTime: $workflow->getTotalCompletionTime(),
        );
    }
}
