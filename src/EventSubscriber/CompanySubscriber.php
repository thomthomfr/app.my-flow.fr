<?php

namespace App\EventSubscriber;

use App\Entity\Company;
use App\Entity\SystemEmail;
use App\Enum\Role;
use App\Event\CompanyBirthdayArrivedEvent;
use App\Event\CompanyUpdatedEvent;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\FrontAPIService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompanySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FrontAPIService $frontAPIService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private SystemEmailRepository $systemEmailRepository,
        private NotificationService $notificationService,
    ){}

    public static function getSubscribedEvents()
    {
        return [
            CompanyUpdatedEvent::NAME => 'onCompanyUpdated',
            CompanyBirthdayArrivedEvent::NAME => 'onCompanyBirthdayArrived',
        ];
    }

    public function onCompanyUpdated(CompanyUpdatedEvent $event)
    {
        $company = $event->getCompany();

        if (!$company instanceof Company) {
            return;
        }

        $response = $this->frontAPIService->pushCompanyToFront($company);

        if (null !== $response && null === $company->getFrontId()) {
            $company->setFrontId($response['id']);
            $this->entityManager->persist($company);
            $this->entityManager->flush();
        }
    }

    public function onCompanyBirthdayArrived(CompanyBirthdayArrivedEvent $event)
    {
        $company = $event->getCompany();

        if (!$company instanceof Company) {
            return;
        }

        $emailSystem = null;

        if ($event->getCompany()) {
            $role = Role::ROLE_CLIENT_ADMIN->value;
            $emailSystem = $this->systemEmailRepository->findOneBy(['code' => SystemEmail::NOTIFICATION_ANNIVERSAIRE_CONTRAT]);
            $clientAdmin = $this->userRepository->findClientAdmin($company->getId(), $role);
        }

        if ($emailSystem !== null) {
            foreach ($clientAdmin ?? [] as $client) {
                $this->notificationService->create($emailSystem, $client);
            }
        }
    }
}
