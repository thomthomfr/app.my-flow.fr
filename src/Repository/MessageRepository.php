<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getLast30MinutesFromCampaign(Campaign $campaign)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.campaign = :campaign')
                ->setParameter('campaign', $campaign)
            ->andWhere('m.createdAt >= :thirtyMinutesAgo')
                ->setParameter('thirtyMinutesAgo', (new \DateTimeImmutable())->sub(new \DateInterval('PT31M'))) // on prend un peu de marge pour la requête
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getLastDayFromCampaigns(array $campaigns)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id), c.name, c.id')
            ->join('m.campaign', 'c')
            ->andWhere('m.campaign IN (:campaigns)')
                ->setParameter('campaigns', $campaigns)
            ->andWhere('m.createdAt >= :lastDay')
                ->setParameter('lastDay', (new \DateTimeImmutable())->sub(new \DateInterval('PT24H')))
            ->addGroupBy('m.campaign')
            ->getQuery()
            ->getResult();
    }

    public function getLastWeekFromCampaigns(array $campaigns)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id), c.name, c.id')
            ->join('m.campaign', 'c')
            ->andWhere('m.campaign IN (:campaigns)')
                ->setParameter('campaigns', $campaigns)
            ->andWhere('m.createdAt >= :lastDay')
                ->setParameter('lastDay', (new \DateTimeImmutable())->sub(new \DateInterval('PT168H')))
            ->addGroupBy('m.campaign')
            ->getQuery()
            ->getResult();
    }
}
