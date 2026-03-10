<?php

namespace App\Repository;

use App\Entity\LoginActivity;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginActivity>
 */
class LoginActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginActivity::class);
    }

    /**
     * Save a login activity record.
     */
    public function save(LoginActivity $activity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($activity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find recent login activities for a user.
     *
     * @return LoginActivity[]
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.user = :user')
            ->setParameter('user', $user)
            ->orderBy('la.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the last login for a user.
     */
    public function findLastLoginByUser(User $user): ?LoginActivity
    {
        return $this->createQueryBuilder('la')
            ->where('la.user = :user')
            ->andWhere('la.activityType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', LoginActivity::TYPE_LOGIN)
            ->orderBy('la.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all login activities with pagination.
     *
     * @return LoginActivity[]
     */
    public function findAllPaginated(int $page = 1, int $limit = 50): array
    {
        return $this->createQueryBuilder('la')
            ->orderBy('la.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total login activities.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find failed login attempts for a given identifier within a time window.
     * Useful for rate limiting or detecting brute force attacks.
     *
     * @return LoginActivity[]
     */
    public function findRecentFailedAttempts(string $identifier, int $minutes = 15): array
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('la')
            ->where('la.userIdentifier = :identifier')
            ->andWhere('la.activityType = :type')
            ->andWhere('la.createdAt >= :since')
            ->setParameter('identifier', $identifier)
            ->setParameter('type', LoginActivity::TYPE_LOGIN_FAILURE)
            ->setParameter('since', $since)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Clean up old login activities.
     *
     * @param int $daysToKeep Number of days to keep login activities
     * @return int Number of deleted records
     */
    public function cleanupOldActivities(int $daysToKeep = 90): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysToKeep} days");

        return $this->createQueryBuilder('la')
            ->delete()
            ->where('la.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }
}
