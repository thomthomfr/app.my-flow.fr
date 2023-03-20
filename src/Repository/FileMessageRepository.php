<?php

namespace App\Repository;

use App\Entity\FileMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FileMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileMessage[]    findAll()
 * @method FileMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileMessage::class);
    }

    // /**
    //  * @return FileMessage[] Returns an array of FileMessage objects
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
    public function findOneBySomeField($value): ?FileMessage
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
