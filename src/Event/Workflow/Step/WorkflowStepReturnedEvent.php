<?php

namespace App\Event\Workflow\Step;

use App\Entity\WorkflowStep;
use Symfony\Contracts\EventDispatcher\Event;

class WorkflowStepReturnedEvent extends Event
{
    public const NAME = 'workflow.step.returned';

    public function __construct(
        protected WorkflowStep $step,
    ){}

    public function getStep(): WorkflowStep
    {
        return $this->step;
    }
}
