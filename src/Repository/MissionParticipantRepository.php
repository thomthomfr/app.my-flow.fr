<?php

namespace App\Repository;

use App\Entity\MissionParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MissionParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method MissionParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method MissionParticipant[]    findAll()
 * @method MissionParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MissionParticipant::class);
    }

    public function findByInitialTime()
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.initialTime IS NOT NULL')
            ->andWhere('m.initialTime != 0')
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return MissionParticipant[] Returns an array of MissionParticipant objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MissionParticipant
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
   public function getMissionForUser($user){
        $query = $this->createQueryBuilder('m')
            ->innerJoin('m.user','user')
            ->innerJoin('m.mission','mission')
            ->innerJoin('mission.campaign','campaign')
            ->andWhere('user = :user')
            ->groupBy('campaign.id')
            ->setParameter('user', $user)
            ->getQuery();
        return $query->getResult();
   }

   public function getAttendeesByMission($user,$mission){
        $query = $this->createQueryBuilder('m')
            ->innerJoin('m.user','user')
            ->innerJoin('m.mission','mission')
            ->innerJoin('mission.campaign','campaign')
            ->andWhere('user = :user')
            ->andWhere('mission = :mission')
            ->groupBy('campaign.id')
            ->setParameter('user', $user)
            ->setParameter('mission', $mission)
            ->getQuery();
        return $query->getResult();
   }
}
