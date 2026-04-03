<?php

namespace App\Repository;

use App\Entity\Patient;
use App\Entity\PatientAccess;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PatientAccess>
 */
class PatientAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientAccess::class);
    }

    public function save(PatientAccess $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PatientAccess $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find the access record for a given user and patient.
     */
    public function findAccess(User $user, Patient $patient): ?PatientAccess
    {
        return $this->findOneBy(['user' => $user, 'patient' => $patient]);
    }

    /**
     * Check if a user has any access to a patient.
     */
    public function hasAccess(User $user, Patient $patient): bool
    {
        return $this->findAccess($user, $patient) !== null;
    }

    /**
     * Find the primary access holder for a patient.
     */
    public function findPrimaryAccess(Patient $patient): ?PatientAccess
    {
        return $this->findOneBy([
            'patient' => $patient,
            'accessLevel' => PatientAccess::LEVEL_PRIMARY,
        ]);
    }

    /**
     * Find all access records for a patient, ordered by level then name.
     *
     * @return PatientAccess[]
     */
    public function findByPatient(Patient $patient): array
    {
        return $this->createQueryBuilder('pa')
            ->join('pa.user', 'u')
            ->where('pa.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('pa.accessLevel', 'ASC') // primary first
            ->addOrderBy('u.surname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all patients a user has access to.
     *
     * @return PatientAccess[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
