<?php

namespace App\Repository;

use App\Entity\NotificationToSend;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationToSend|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationToSend|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationToSend[]    findAll()
 * @method NotificationToSend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationToSendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationToSend::class);
    }

    public function checkIfNotificationAwaiting(User $user)
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.sendTo = :user')
            ->setParameter('user', $user)
            ->andWhere('n.sendAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
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
