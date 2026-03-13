<?php

namespace App\Repository;

use App\Entity\Transplant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transplant>
 */
class TransplantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transplant::class);
    }

    /**
     * @return Transplant[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('t.transplantDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Transplant $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(Transplant $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
