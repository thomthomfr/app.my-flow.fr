<?php

namespace App\Command;

use App\Event\CompanyBirthdayArrivedEvent;
use App\Repository\CreditHistoryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'notifications:birthday-contract',
)]
class NotificationBirthdayContractCommand extends Command
{
    public function __construct(
        private CreditHistoryRepository $creditHistoryRepository,
        private RouterInterface $router,
        private MailerInterface $mailer,
        private EventDispatcherInterface $dispatcher,
        string $name = null,
    ){
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notificationsContract = $this->creditHistoryRepository->findAll();
        $now = new \DateTime();

        foreach ($notificationsContract as $notification) {
            if ($notification->getCreditExpiredAt()->format('Y-m-d') == $now->format('Y-m-d')){
                $event = new CompanyBirthdayArrivedEvent($notification->getCompany());
                $this->dispatcher->dispatch($event, CompanyBirthdayArrivedEvent::NAME);
            }
        }
        return Command::SUCCESS;
    }
}
