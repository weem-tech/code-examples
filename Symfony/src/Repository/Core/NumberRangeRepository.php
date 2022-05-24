<?php

namespace App\Repository\Core;

use App\Entity\Core\NumberRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NumberRange|null find($id, $lockMode = null, $lockVersion = null)
 * @method NumberRange|null findOneBy(array $criteria, array $orderBy = null)
 * @method NumberRange[]    findAll()
 * @method NumberRange[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NumberRangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NumberRange::class);
    }

//    /**
//     * @return NumberRange[] Returns an array of NumberRange objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NumberRange
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
