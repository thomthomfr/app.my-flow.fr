<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Service|null find($id, $lockMode = null, $lockVersion = null)
 * @method Service|null findOneBy(array $criteria, array $orderBy = null)
 * @method Service[]    findAll()
 * @method Service[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findServiceByUser($thisCompany)
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->andWhere('u.company = :thisCompany')
            ->setParameter('thisCompany', $thisCompany)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllServiceByUser($user)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    /**
     * Deletes the services that the product is marked as deleted
     *
     * @return int|mixed|string
     */
    public function deleteAllMarked()
    {
        $ids = $this->createQueryBuilder('s')
            ->select('s.id')
            ->join('s.product', 'p')
            ->where('p.deleted = 1')
            ->getQuery()
            ->getResult();

        return $this->createQueryBuilder('s')
            ->where('s.id in (:ids)')
                ->setParameter('ids', $ids)
            ->delete()
            ->getQuery()
            ->execute();
    }
}
