<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class MissionDesiredDeliveryUpdatedAfterValidationEvent extends Event
{
    public const NAME = 'mission.desiredDelivery.updated.afterValidation';

    public function __construct(
        protected Mission $mission,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }
}
