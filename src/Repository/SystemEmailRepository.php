<?php

namespace App\Repository;

use App\Entity\SystemEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SystemEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemEmail[]    findAll()
 * @method SystemEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SystemEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemEmail::class);
    }

    // /**
    //  * @return SystemEmail[] Returns an array of SystemEmail objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SystemEmail
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
