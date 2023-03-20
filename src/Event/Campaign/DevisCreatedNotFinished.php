<?php

namespace App\Event\Campaign;

use App\Entity\Campaign;
use App\Entity\User;
use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class DevisCreatedNotFinished extends Event
{
    public const NAME = 'devis.not.finished';

    public function __construct(
        protected Campaign $campaign,
        protected User $user,
    ){}

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
