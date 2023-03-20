<?php

namespace App\Event\Admin;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionNotificationSubContractorNotActivatedEvent extends Event
{
    public const NAME = 'mission.notification.sub.contractor.not.activated';

    public function __construct(
        protected Mission $mission,
        protected User $user,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
