<?php

namespace App\Repository;

use App\Entity\BiologicalResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BiologicalResult>
 */
class BiologicalResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BiologicalResult::class);
    }

    /**
     * @return BiologicalResult[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('b.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(BiologicalResult $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(BiologicalResult $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
