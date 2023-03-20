<?php

namespace App\Repository;

use App\Entity\InfoMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InfoMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method InfoMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method InfoMission[]    findAll()
 * @method InfoMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfoMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InfoMission::class);
    }

    // /**
    //  * @return InfoMission[] Returns an array of InfoMission objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InfoMission
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
