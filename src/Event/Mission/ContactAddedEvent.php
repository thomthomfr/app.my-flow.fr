<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use App\Entity\MissionParticipant;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ContactAddedEvent extends Event
{
    public const NAME = 'mission.contact.added';

    public function __construct(
        protected Mission $mission,
        protected User $contact,
        protected MissionParticipant $participant,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getContact(): User
    {
        return $this->contact;
    }

    public function getParticipant(): MissionParticipant
    {
        return $this->participant;
    }
}
