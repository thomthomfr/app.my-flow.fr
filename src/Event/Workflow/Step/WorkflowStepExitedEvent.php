<?php

namespace App\Event\Workflow\Step;

use App\Entity\WorkflowStep;
use Symfony\Contracts\EventDispatcher\Event;

class WorkflowStepExitedEvent extends Event
{
    public const NAME = 'workflow.step.exited';

    public function __construct(
        protected WorkflowStep $step,
    ){}

    public function getStep(): WorkflowStep
    {
        return $this->step;
    }
}
