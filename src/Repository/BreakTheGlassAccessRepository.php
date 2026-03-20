<?php

namespace App\Repository;

use App\Entity\BreakTheGlassAccess;
use App\Entity\Patient;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BreakTheGlassAccess>
 */
class BreakTheGlassAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BreakTheGlassAccess::class);
    }

    /**
     * Check if a user has an active (non-expired) break-the-glass access for a patient.
     */
    public function hasActiveAccess(User $user, Patient $patient): bool
    {
        return $this->findActiveAccess($user, $patient) !== null;
    }

    /**
     * Find the active (non-expired) break-the-glass access for a user+patient pair.
     */
    public function findActiveAccess(User $user, Patient $patient): ?BreakTheGlassAccess
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.patient = :patient')
            ->andWhere('b.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('patient', $patient)
            ->setParameter('now', $now)
            ->orderBy('b.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find active BTG accesses for a user across multiple patients.
     *
     * @param int[] $patientIds
     * @return array<int, BreakTheGlassAccess> Indexed by patient ID
     */
    public function findActiveAccessesForPatients(User $user, array $patientIds): array
    {
        if (empty($patientIds)) {
            return [];
        }

        $now = new \DateTimeImmutable();

        $results = $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.patient IN (:patientIds)')
            ->andWhere('b.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('patientIds', $patientIds)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $btg) {
            $indexed[$btg->getPatient()->getId()] = $btg;
        }

        return $indexed;
    }

    /**
     * Find all break-the-glass accesses, ordered by most recent first.
     *
     * @return BreakTheGlassAccess[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.accessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(BreakTheGlassAccess $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
