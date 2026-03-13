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
    public function search(?string $cristalNumber, ?string $bloodGroup, ?string $donorType): array
    {
        $qb = $this->createQueryBuilder('d');

        if ($cristalNumber) {
            $qb->andWhere('d.cristalNumber LIKE :cristal')
               ->setParameter('cristal', '%' . $cristalNumber . '%');
        }

        if ($bloodGroup) {
            $qb->andWhere('d.bloodGroup = :blood')
               ->setParameter('blood', $bloodGroup);
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
