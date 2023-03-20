<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class MissionWithoutSubContractorCheckedEvent extends Event
{
    public const NAME = 'mission.without.subcontractor.checked';

    public function __construct(
        protected Mission $mission,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }
}
