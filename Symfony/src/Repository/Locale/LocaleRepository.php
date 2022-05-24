<?php

namespace App\Repository\Locale;

use App\Entity\Locale\Locale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Locale|null find($id, $lockMode = null, $lockVersion = null)
 * @method Locale|null findOneBy(array $criteria, array $orderBy = null)
 * @method Locale[]    findAll()
 * @method Locale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Locale::class);
    }

    /**
     * @param $iso
     * @return Locale|null
     * @throws NonUniqueResultException
     */
    public function getIdByIso($iso)
    {
        return $this->createQueryBuilder('l')
            ->select('l.id')
            ->andWhere('l.iso = :iso')
            ->setParameter('iso', $iso)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    // /**
    //  * @return Locale[] Returns an array of Locale objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
