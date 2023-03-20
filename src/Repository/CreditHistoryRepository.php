<?php

namespace App\Repository;

use App\Entity\CreditHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CreditHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditHistory[]    findAll()
 * @method CreditHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditHistory::class);
    }


    public function findAvailableCredit($company)
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere(':now > c.startDateContract')
            ->andWhere(':now < c.creditExpiredAt')
            ->andWhere('c.company = :company')
            ->setParameter('now', $now)
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult()
            ;
    }
    
    // /**
    //  * @return CreditHistory[] Returns an array of CreditHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CreditHistory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
