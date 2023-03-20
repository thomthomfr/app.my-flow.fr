<?php

namespace App\Command;

use App\Entity\Message;
use App\Event\Campaign\CampaignCreatedEvent;
use App\Event\Chat\MessageSentEvent;
use App\Repository\CampaignRepository;
use App\Repository\UserRepository;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Campaign;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'email:email-auto-commande:read',
)]
class EmailCommandeComand extends Command
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private CampaignRepository $campaignRepository,
        private EventDispatcherInterface $dispatcher,
        string $name = null,
    ){
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = new Server($this->parameterBag->get('imap_host'));
        $connection = $server->authenticate($this->parameterBag->get('imap_username'), $this->parameterBag->get('imap_password'));
        $mailbox = $connection->getMailbox('INBOX');
        $messages = $mailbox->getMessages(
            new Unseen(),
            \SORTDATE,
            true
        );

        foreach ($messages as $message) {
            $subject = $message->getSubject();
            $content = $message->getContent();
            $text = $message->getBodyText();
            $sender = $message->getSender()[0]->getAddress();

            $user = $this->userRepository->findOneBy(['email' => $sender]);

            if (!empty($user)) {
                if (preg_match('/(.+)##- Conservez cette ligne sur vos réponses. Tout ce qui se trouve au-dessus de cette ligne sera inséré sur la mission et partagé avec l\'ensemble des participants au projet.+\[CAMPAIGN_ID=(.+)\] -##/ms', $text, $matches)) {
                    if (isset($matches[1]) && isset($matches[2])) {
                        $chatMessage = $matches[1];
                        $campaignId = $matches[2];
                        if (null !== $campaign = $this->campaignRepository->find($campaignId)) {
                            $chatMessage = (new Message())
                                ->setContent(nl2br($chatMessage))
                                ->setUser($user)
                                ->setCampaign($campaign);

                            $this->entityManager->persist($chatMessage);
                            $this->entityManager->flush();

                            $event = new MessageSentEvent($chatMessage);
                            $this->dispatcher->dispatch($event, MessageSentEvent::NAME);
                        }
                    }
                } else {
                    $campaign = new Campaign();
                    $campaign->setBrief($content);
                    $campaign->setName($subject);
                    $campaign->setOrderedBy($user);
                    $campaign->setState('provisional');
                    $campaign->setCompany($user->getCompany());

                    $this->entityManager->persist($campaign);
                    $this->entityManager->flush();

                    $event = new CampaignCreatedEvent($campaign);
                    $this->dispatcher->dispatch($event, CampaignCreatedEvent::NAME);
                }
            }

            $message->markAsSeen();
        }

        return Command::SUCCESS;
    }
}
