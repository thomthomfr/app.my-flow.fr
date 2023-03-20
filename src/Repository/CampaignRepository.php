<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Campaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campaign[]    findAll()
 * @method Campaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    public function findForSubcontractor(User $user, $role=null)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->join('c.missions', 'm')
            ->join('m.participants', 'p')
            ->andWhere('p.user = :user')
                ->setParameter('user', $user);
        //     ->andWhere('p.role = :role');
        //   if($role==null){
        //     $queryBuilder->setParameter('role', Role::ROLE_SUBCONTRACTOR->value);

        //   }
        //   else{
        //     $queryBuilder->setParameter('role', $role);
        //   }
           return $queryBuilder->addOrderBy('m.desiredDelivery', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function orderedByDesiredDelivery(?Company $company = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.missions', 'm')
                ->addOrderBy('m.desiredDelivery', 'ASC')
        ;

        if (null !== $company) {
            $qb->andWhere('c.company = :company')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
