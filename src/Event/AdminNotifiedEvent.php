<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class AdminNotifiedEvent extends Event
{
    public const NAME = 'admin.notified';

    public function __construct(
        protected User $subcontractor,
    ){}

    public function getSubcontractor(): User
    {
        return $this->subcontractor;
    }
}
