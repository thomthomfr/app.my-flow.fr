<?php

namespace App\Repository;

use App\Entity\FileMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FileMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileMission[]    findAll()
 * @method FileMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileMission::class);
    }

    // /**
    //  * @return FileMission[] Returns an array of FileMission objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FileMission
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
