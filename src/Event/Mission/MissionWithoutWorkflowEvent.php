<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class MissionWithoutWorkflowEvent extends Event
{
    public const NAME = 'mission.withoutWorkflow';

    public function __construct(
        protected Mission $mission,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }
}
