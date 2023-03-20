<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByRoleClients($role, $observer, $roleClientAdmin)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->orWhere('u.roles LIKE :observer')
            ->orWhere('u.roles LIKE :roleClientAdmin')
            ->setParameter('role', '%"'.$role.'"%')
            ->setParameter('observer', '%"'.$observer.'"%')
            ->setParameter('roleClientAdmin', '%"'.$roleClientAdmin.'"%')
            ->andWhere('u.deleted = 0')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByRole($role)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->andWhere('u.deleted = 0')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByRoleAndNotEnabled($role)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->andWhere('u.deleted = 0')
            ->andWhere('u.enabled = 0')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findClientAdmin($company, $role)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->setParameter('role', '%"'.$role.'"%')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findClientByCompany($thisCompany, $roleClient, $roleClientAdmin)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :thisCompany')
            ->andWhere('u.roles LIKE :roleClient OR u.roles LIKE :roleClientAdmin')
            ->andWhere('u.deleted = 0')
            ->setParameter('thisCompany', $thisCompany)
            ->setParameter('roleClient', '%"'.$roleClient.'"%')
            ->setParameter('roleClientAdmin', '%"'.$roleClientAdmin.'"%')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findClientAdminByCompany($thisCompany, $roleClientAdmin)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :thisCompany')
            ->andWhere('u.roles LIKE :roleClientAdmin')
            ->andWhere('u.deleted = 0')
            ->setParameter('thisCompany', $thisCompany)
            ->setParameter('roleClientAdmin', '%"'.$roleClientAdmin.'"%')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findSubContractorByCompany($thisCompany, $roleSubContractor)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :thisCompany')
            ->andWhere('u.roles LIKE :roleSubContractor')
            ->setParameter('thisCompany', $thisCompany)
            ->setParameter('roleSubContractor', '%"'.$roleSubContractor.'"%')
            ->getQuery()
            ->getResult()
            ;
    }

    public function apiQuerySearch(string $query, string $role)
    {
        $conn = $this->getEntityManager()
            ->getConnection();

        $sql = 'SELECT * FROM users WHERE email LIKE :query AND roles LIKE :role';
        $stmt = $conn->prepare($sql);
        $stmt = $stmt->executeQuery(['query' => '%'.$query.'%', 'role' => '%'.$role.'%']);

        return $stmt->fetchAllAssociative();
    }

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
