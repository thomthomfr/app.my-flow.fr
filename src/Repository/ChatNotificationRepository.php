<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\ChatNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChatNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatNotification[]    findAll()
 * @method ChatNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatNotification::class);
    }

    public function checkIfNotificationAwaiting(User $user, Campaign $campaign): bool
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.sendTo = :user')
                ->setParameter('user', $user)
            ->andWhere('n.sendAt > :now')
                ->setParameter('now', new \DateTimeImmutable())
            ->andWhere('n.campaign = :campaign')
                ->setParameter('campaign', $campaign)
            ->getQuery()
            ->getSingleScalarResult();

        return $qb > 0;
    }

    public function toSendNow()
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.sendAt LIKE :now')
                ->setParameter('now', '%'.(new \DateTimeImmutable())->format('Y-m-d H:i').'%')
            ->getQuery()
            ->getResult();
    }
}
