<?php

namespace App\Repository;

use App\Entity\MedicalHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedicalHistory>
 */
class MedicalHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalHistory::class);
    }

    /**
     * @return MedicalHistory[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('m.diagnosisDate', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(MedicalHistory $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(MedicalHistory $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
