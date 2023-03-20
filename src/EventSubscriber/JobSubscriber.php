<?php

namespace App\EventSubscriber;

use App\Entity\Job;
use App\Event\Job\JobCreatedEvent;
use App\Event\Job\JobDeletedEvent;
use App\Event\Job\JobUpdatedEvent;
use App\Service\FrontAPIService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FrontAPIService $frontAPIService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            JobCreatedEvent::NAME => 'onJobCreated',
            JobUpdatedEvent::NAME => 'onJobUpdated',
            JobDeletedEvent::NAME => 'onJobDeleted',
        ];
    }

    public function onJobCreated(JobCreatedEvent $event): void
    {
        $job = $event->getJob();

        if (!$job instanceof Job) {
            return;
        }

        $this->frontAPIService->createJobOnFront($job);
    }

    public function onJobUpdated(JobUpdatedEvent $event): void
    {
        $job = $event->getJob();

        if (!$job instanceof Job) {
            return;
        }

        $this->frontAPIService->updateJobOnFront($job);
    }

    public function onJobDeleted(JobDeletedEvent $event): void
    {
        $job = $event->getJob();

        if (!$job instanceof Job) {
            return;
        }

        $this->frontAPIService->deleteJobOnFront($job);
    }
}
