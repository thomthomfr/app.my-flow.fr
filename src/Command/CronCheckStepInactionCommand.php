<?php

namespace App\Command;

use App\Enum\Operator;
use App\Enum\Trigger;
use App\Repository\WorkflowStepRepository;
use App\Service\TriggerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'cron:check-step-inaction',
    description: 'Checks all active missions to see if we need to execute the inaction the inaction trigger',
)]
class CronCheckStepInactionCommand extends Command
{
    public function __construct(
        private WorkflowStepRepository $workflowStepRepository,
        private TriggerService $triggerService,
        string $name = null,
    ){
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $steps = $this->workflowStepRepository->findBy(['active' => true]);

        foreach ($steps as $step) {
            foreach ($step->getActions() as $action) {
                foreach ($action->getTriggers() as $trigger) {
                    if ($trigger->getTriggerType() === Trigger::INACTION) {
                        switch ($trigger->getOperator()) {
                            case Operator::EQUAL:
                                if ((new \DateTime())->format('YmdHi') == $step->getStartDate()->format('YmdHi')) {
                                    $this->triggerService->execute($trigger);
                                }
                                break;

                            case Operator::GREATER_THAN:
                                if (new \DateTime() > $step->getStartDate()->add(new \DateInterval('PT'.$trigger->getTimePeriod()->value.'H'))) {
                                    $this->triggerService->execute($trigger);
                                }
                                break;

                            case Operator::GREATER_THAN_OR_EQUAL:
                                if (new \DateTime() >= $step->getStartDate()->add(new \DateInterval('PT'.$trigger->getTimePeriod()->value.'H'))) {
                                    $this->triggerService->execute($trigger);
                                }
                                break;

                            case Operator::LOWER_THAN:
                                if (new \DateTime() < $step->getStartDate()->add(new \DateInterval('PT'.$trigger->getTimePeriod()->value.'H'))) {
                                    $this->triggerService->execute($trigger);
                                }
                                break;

                            case Operator::LOWER_THAN_OR_EQUAL:
                                if (new \DateTime() <= $step->getStartDate()->add(new \DateInterval('PT'.$trigger->getTimePeriod()->value.'H'))) {
                                    $this->triggerService->execute($trigger);
                                }
                                break;
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
