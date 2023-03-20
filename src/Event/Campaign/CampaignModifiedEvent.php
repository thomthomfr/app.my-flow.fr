<?php

namespace App\Event\Campaign;

use App\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignModifiedEvent extends Event
{
    public const NAME = 'campaign.modified';

    public function __construct(
        protected Campaign $campaign,
    ){}

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

}
