<?php

namespace App\Repository;

use App\Entity\Figurine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FigurineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Figurine::class); }

    /** @return list<Figurine> */
    public function findLatest(int $limit = 12): array
    {
        return $this->createQueryBuilder('f')->addOrderBy('f.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
