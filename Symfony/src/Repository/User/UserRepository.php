<?php

namespace App\Repository\User;

use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Intl\Intl;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findById($id)
    {
        $builder = $this->createQueryBuilder('u');

        $result = $builder
            ->addSelect(['s', 'v'])
            ->leftJoin('u.shops', 's')
            ->leftJoin('u.vouchers', 'v')
            ->andWhere($builder->expr()->eq('u.id', ':id'))
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        $user = array_shift(array_values($result)) ?: [];
        if (!empty($user)) {
            $user['country'] = Intl::getRegionBundle()->getCountryName($user['country']);
        }

        return $user;
    }

    /**
     * @return mixed
     */
    public function findAllRegular()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles NOT LIKE :roles')
            ->setParameter('roles', '%' . User::ROLE_ADMIN . '%')
            ->getQuery()
            ->getResult();
    }
}
