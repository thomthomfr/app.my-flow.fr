<?php

namespace App\Repository;

use App\Entity\WorkflowStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkflowStep|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkflowStep|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkflowStep[]    findAll()
 * @method WorkflowStep[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkflowStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStep::class);
    }

    // /**
    //  * @return WorkflowSteps[] Returns an array of WorkflowSteps objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WorkflowSteps
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
