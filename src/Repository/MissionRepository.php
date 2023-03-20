<?php

namespace App\Repository;

use App\Entity\Mission;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Mission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mission[]    findAll()
 * @method Mission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mission::class);
    }

    public function findMissionsFor(Role $role, User $user, bool $active)
    {
        $qb = $this->createQueryBuilder('m');

        if ($role === Role::ROLE_CLIENT) {
            $qb->join('m.campaign', 'c')
            ->andWhere('c.company = :company')
                ->setParameter('company', $user->getCompany());
        }

        if ($role === Role::ROLE_SUBCONTRACTOR) {
            $qb->join('m.participants', 'p')
                    ->andWhere('p.user = :user')
                ->setParameter('user', $user)
                    ->andWhere('p.role = :role')
                ->setParameter('role', $role->value);
        }

        if ($active) {
            $qb->andWhere('m.state IN (:active)')
                ->setParameter('active', ['provisional', 'in_progress', 'waiting', 'waiting_activated', 'paused']);
        } else {
            $qb->andWhere('m.state IN (:notActive)')
                ->setParameter('notActive', ['cancelled', 'archived']);
        }

        return $qb
            ->addOrderBy('m.desiredDelivery', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findCreatedYesterday()
    {
        return $this->createQueryBuilder('m')
        ->andWhere('m.createdAt = :yesterday')
            ->setParameter('yesterday', (new \DateTime())->sub(new \DateInterval('P1D')))
        ->getQuery()
        ->getResult();
    }
}
