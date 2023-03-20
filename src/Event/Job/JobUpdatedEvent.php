<?php

namespace App\Event\Job;

use App\Entity\Job;
use Symfony\Contracts\EventDispatcher\Event;

class JobUpdatedEvent extends Event
{
    public const NAME = 'job.updated';

    public function __construct(
        protected Job $job,
    ){}

    public function getJob(): Job
    {
        return $this->job;
    }
}
