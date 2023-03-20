<?php

namespace App\Command;

use App\Event\Admin\MissionNotificationSubContractorNotActivatedEvent;
use App\Repository\MissionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'notifications:subContractor-not-activated-in-time',
)]
class AdminNotificationMissionNotActivatedBySubcontractorCommand extends Command
{
    public function __construct(
        private MissionRepository $missionRepository,
        private EventDispatcherInterface $dispatcher,
        string $name = null,
    ){
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $missions = $this->missionRepository->findCreatedYesterday();

        foreach ($missions as $mission){
            foreach ($mission->getParticipants() as $participant){
                if (empty($participant->getActivatedAt())){
                    $event = new MissionNotificationSubContractorNotActivatedEvent($mission);
                    $this->dispatcher->dispatch($event, MissionNotificationSubContractorNotActivatedEvent::NAME);

                    break;
                }
            }
        }
        return Command::SUCCESS;
    }
}
