<?php

namespace App\Repository;

use App\Entity\PasswordHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordHistory>
 */
class PasswordHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordHistory::class);
    }

    public function save(PasswordHistory $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get the N most recent password hashes for a user.
     *
     * @return list<string>
     */
    public function findRecentHashesByUser(User $user, int $limit = 5): array
    {
        $results = $this->createQueryBuilder('ph')
            ->select('ph.hashedPassword')
            ->where('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.changedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        return $results;
    }

    /**
     * Get the most recent password change date for a user.
     */
    public function findLastChangeDate(User $user): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('ph')
            ->select('ph.changedAt')
            ->where('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.changedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['changedAt'] : null;
    }

    /**
     * Get the full password history for a user, ordered by most recent.
     *
     * @return list<PasswordHistory>
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('ph')
            ->where('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.changedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
