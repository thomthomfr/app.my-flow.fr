<?php

namespace App\Event\Workflow\Step;

use App\Entity\Workflow;
use Symfony\Contracts\EventDispatcher\Event;

class WorkflowStepDeletedEvent extends Event
{
    public const NAME = 'workflow.step.deleted';

    public function __construct(
        protected Workflow $workflow,
    ){}

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }
}
