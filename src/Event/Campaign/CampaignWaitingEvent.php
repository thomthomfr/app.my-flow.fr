<?php

namespace App\Event\Campaign;

use App\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignWaitingEvent extends Event
{
    public const NAME = 'campaign.waiting';

    public function __construct(
        protected Campaign $campaign,
        private bool $systemEmail = false,
    ){}

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getSystemEmail(): bool
    {
        return $this->systemEmail;
    }
}
