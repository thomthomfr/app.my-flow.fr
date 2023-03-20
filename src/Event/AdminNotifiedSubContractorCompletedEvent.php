<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class AdminNotifiedSubContractorCompletedEvent extends Event
{
    public const NAME = 'admin.notified.sub.contractor.completed';

    public function __construct(
        protected User $subcontractor,
        protected bool $sendNotification = false,
        protected bool $thankYouNotification = false,
        protected bool $updateProfileNotification = false,
    ){}

    public function getSubcontractor(): User
    {
        return $this->subcontractor;
    }

    public function getSendNotification(): bool
    {
        return $this->sendNotification;
    }

    public function getThankYouNotification(): bool
    {
        return $this->thankYouNotification;
    }

    public function getUpdateProfileNotification(): bool
    {
        return $this->updateProfileNotification;
    }
}
