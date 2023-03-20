<?php

namespace App\Event\Campaign;

use App\Entity\Campaign;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignValidatedEvent extends Event
{
    public const NAME = 'campaign.validated';

    public function __construct(
        protected Campaign $campaign,
    ){}

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
