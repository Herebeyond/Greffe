<?php

namespace App\Repository;

use App\Entity\Donor;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Donor>
 */
class DonorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donor::class);
    }

    /**
     * @return Donor[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Donor[]
     */
    public function findByType(string $typeCode): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.donorType', 'dt')
            ->where('dt.code = :type')
            ->setParameter('type', $typeCode)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Donor[]
     */
    public function search(?string $cristalNumber, array $bloodTypes, ?string $donorType): array
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.bloodGroup', 'bg')
            ->join('d.donorType', 'dt');

        if ($cristalNumber) {
            $qb->andWhere('d.cristalNumber LIKE :cristal')
               ->setParameter('cristal', '%' . $cristalNumber . '%');
        }

        if (!empty($bloodTypes)) {
            $orConditions = [];
            foreach ($bloodTypes as $i => $type) {
                $group = rtrim($type, '+-');
                $rh = str_ends_with($type, '+') ? '+' : '-';
                $orConditions[] = sprintf('(bg.code = :bg%d AND d.rhesus = :rh%d)', $i, $i);
                $qb->setParameter('bg' . $i, $group)
                   ->setParameter('rh' . $i, $rh);
            }
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        if ($donorType) {
            $qb->andWhere('dt.code = :type')
               ->setParameter('type', $donorType);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Donor $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Find donors linked to a user's assigned patients through transplants.
     *
     * @return Donor[]
     */
    public function findByPractitioner(User $user): array
    {
        // First get distinct donor IDs linked to the user's patients
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(t.donor) AS donorId')
            ->from('App\Entity\Transplant', 't')
            ->join('t.patient', 'p')
            ->join('p.authorizedPractitioners', 'u')
            ->where('u = :user')
            ->andWhere('t.donor IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $donorIds = array_unique(array_column($rows, 'donorId'));

        if (empty($donorIds)) {
            return [];
        }

        return $this->createQueryBuilder('d')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $donorIds)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search donors linked to a user's assigned patients through transplants.
     *
     * @return Donor[]
     */
    public function searchByPractitioner(User $user, ?string $cristalNumber, array $bloodTypes, ?string $donorType): array
    {
        // First get distinct donor IDs linked to the user's patients
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(t.donor) AS donorId')
            ->from('App\Entity\Transplant', 't')
            ->join('t.patient', 'p')
            ->join('p.authorizedPractitioners', 'u')
            ->where('u = :user')
            ->andWhere('t.donor IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $donorIds = array_unique(array_column($rows, 'donorId'));

        if (empty($donorIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('d')
            ->join('d.bloodGroup', 'bg')
            ->join('d.donorType', 'dt')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $donorIds);

        if ($cristalNumber) {
            $qb->andWhere('d.cristalNumber LIKE :cristal')
               ->setParameter('cristal', '%' . $cristalNumber . '%');
        }

        if (!empty($bloodTypes)) {
            $orConditions = [];
            foreach ($bloodTypes as $i => $type) {
                $group = rtrim($type, '+-');
                $rh = str_ends_with($type, '+') ? '+' : '-';
                $orConditions[] = sprintf('(bg.code = :bg%d AND d.rhesus = :rh%d)', $i, $i);
                $qb->setParameter('bg' . $i, $group)
                   ->setParameter('rh' . $i, $rh);
            }
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        if ($donorType) {
            $qb->andWhere('dt.code = :type')
               ->setParameter('type', $donorType);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function remove(Donor $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
