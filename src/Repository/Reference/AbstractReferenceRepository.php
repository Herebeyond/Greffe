<?php

namespace App\Repository\Reference;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractReferenceRepository extends ServiceEntityRepository
{
    /**
     * @return object[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = true')
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCode(string $code): ?object
    {
        return $this->findOneBy(['code' => $code]);
    }
}
