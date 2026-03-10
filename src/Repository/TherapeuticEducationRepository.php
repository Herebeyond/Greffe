<?php

namespace App\Repository;

use App\Entity\TherapeuticEducation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TherapeuticEducation>
 */
class TherapeuticEducationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TherapeuticEducation::class);
    }

    /**
     * @return TherapeuticEducation[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('t.sessionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TherapeuticEducation $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(TherapeuticEducation $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
