<?php

namespace App\Event\Job;

use App\Entity\Job;
use Symfony\Contracts\EventDispatcher\Event;

class JobDeletedEvent extends Event
{
    public const NAME = 'job.deleted';

    public function __construct(
        protected Job $job,
    ){}

    public function getJob(): Job
    {
        return $this->job;
    }
}
