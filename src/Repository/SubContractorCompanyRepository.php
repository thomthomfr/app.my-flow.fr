<?php

namespace App\Repository;

use App\Entity\SubContractorCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubContractorCompany|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubContractorCompany|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubContractorCompany[]    findAll()
 * @method SubContractorCompany[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubContractorCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubContractorCompany::class);
    }

    // /**
    //  * @return SubContractorCompany[] Returns an array of SubContractorCompany objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SubContractorCompany
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
