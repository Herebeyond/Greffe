<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $auditLog, bool $flush = true): void
    {
        $this->getEntityManager()->persist($auditLog);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return AuditLog[]
     */
    public function findFiltered(
        ?string $userIdentifier = null,
        ?string $action = null,
        ?string $entityType = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        int $page = 1,
        int $limit = 50,
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($userIdentifier) {
            $qb->andWhere('a.userIdentifier LIKE :user')
                ->setParameter('user', '%' . $userIdentifier . '%');
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('a.entityType = :entityType')
                ->setParameter('entityType', $entityType);
        }

        if ($dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(
        ?string $userIdentifier = null,
        ?string $action = null,
        ?string $entityType = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
    ): int {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if ($userIdentifier) {
            $qb->andWhere('a.userIdentifier LIKE :user')
                ->setParameter('user', '%' . $userIdentifier . '%');
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('a.entityType = :entityType')
                ->setParameter('entityType', $entityType);
        }

        if ($dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function findDistinctEntityTypes(): array
    {
        $results = $this->createQueryBuilder('a')
            ->select('DISTINCT a.entityType')
            ->where('a.entityType IS NOT NULL')
            ->orderBy('a.entityType', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_filter($results);
    }
}
