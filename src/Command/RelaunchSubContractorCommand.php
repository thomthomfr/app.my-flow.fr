<?php
namespace App\Command;

use App\Enum\Role;
use App\Event\AdminNotifiedSubContractorNoResponseEvent;
use App\Event\SubContractorRelaunchedEvent;
use App\Service\EmailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class RelaunchSubContractorCommand extends Command
{
    protected static $defaultName = 'app:relaunch-sub-contractor-email';

    private $entityManager;
    private $validator;
    private $users;
    private $emailService;
    private $dispatcher;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $users, EmailService $emailService, EventDispatcherInterface $dispatcher)
    {
        parent::__construct();

        $this->entityManager = $em;
        $this->users = $users;
        $this->validator = $validator;
        $this->emailService = $emailService;
        $this->dispatcher = $dispatcher;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $today = new \DateTime();
        $today2 = new \DateTime();

        $flag = false;
        $role = Role::ROLE_SUBCONTRACTOR->value;
        $subContractorNotEnabled = $this->users->findByRoleAndNotEnabled($role);
        foreach ($subContractorNotEnabled as $user){
            if ($user->getCreatedAt()->format('Y-m-d') == $today->sub(new \DateInterval('P2D'))->format('Y-m-d')){
                $event = new SubContractorRelaunchedEvent($user);
                $this->dispatcher->dispatch($event, SubContractorRelaunchedEvent::NAME);

            }elseif(($user->getCreatedAt())->format('Y-m-d') == ($today2->sub(new \DateInterval('P3D'))->format('Y-m-d'))){
                $event = new AdminNotifiedSubContractorNoResponseEvent($user);
                $this->dispatcher->dispatch($event, AdminNotifiedSubContractorNoResponseEvent::NAME);
                $flag = true;
            }
        }
        if ($flag == true){
            $io = new SymfonyStyle($input, $output);
            $io->success('Email(s) envoyé avec succès');
        }

        return 0;
    }
}
