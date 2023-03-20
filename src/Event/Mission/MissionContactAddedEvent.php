<?php

namespace App\Event\Mission;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\User;

class MissionContactAddedEvent extends Event
{
    public const NAME = 'mission.contact.add';

    public function __construct(
        protected User $contact,
    ){}
    public function getContact(): User
    {
        return $this->contact;
    }

}
