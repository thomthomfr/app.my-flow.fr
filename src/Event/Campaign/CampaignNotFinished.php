<?php

namespace App\Event\Campaign;

use App\Entity\Campaign;
use App\Entity\User;
use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignNotFinishedEvent extends Event
{
    public const NAME = 'campaign.not.finished';

    public function __construct(
        protected Campaign $campaign,
        protected Mission $mission,
        protected User $user,
    ){}

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
