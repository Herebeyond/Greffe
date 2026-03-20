<?php

namespace App\Repository;

use App\Entity\Donor;
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
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.donorType = :type')
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Donor[]
     */
    public function search(?string $cristalNumber, array $bloodTypes, ?string $donorType): array
    {
        $qb = $this->createQueryBuilder('d');

        if ($cristalNumber) {
            $qb->andWhere('d.cristalNumber LIKE :cristal')
               ->setParameter('cristal', '%' . $cristalNumber . '%');
        }

        if (!empty($bloodTypes)) {
            $orConditions = [];
            foreach ($bloodTypes as $i => $type) {
                $group = rtrim($type, '+-');
                $rh = str_ends_with($type, '+') ? '+' : '-';
                $orConditions[] = sprintf('(d.bloodGroup = :bg%d AND d.rhesus = :rh%d)', $i, $i);
                $qb->setParameter('bg' . $i, $group)
                   ->setParameter('rh' . $i, $rh);
            }
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        if ($donorType) {
            $qb->andWhere('d.donorType = :type')
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

    public function remove(Donor $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
