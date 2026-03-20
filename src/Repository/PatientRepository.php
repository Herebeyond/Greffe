<?php

namespace App\Repository;

use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Patient>
 */
class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    /**
     * Save a patient entity.
     */
    public function save(Patient $patient, bool $flush = true): void
    {
        $this->getEntityManager()->persist($patient);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a patient entity.
     */
    public function remove(Patient $patient, bool $flush = true): void
    {
        $this->getEntityManager()->remove($patient);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Search patients by criteria.
     * 
     * Note: firstName and lastName are encrypted, so we fetch all and filter in PHP.
     * For large datasets, consider implementing a search index.
     *
     * @param array{
     *     lastName?: string,
     *     firstName?: string,
     *     fileNumber?: string,
     *     city?: string
     * } $criteria Search criteria (at least one required)
     * @param int $page Page number (1-indexed)
     * @param int $limit Results per page
     * @return array{patients: Patient[], total: int, hasMore: bool}
     */
    public function searchPaginated(array $criteria, int $page = 1, int $limit = 50): array
    {
        // Build query for non-encrypted fields
        $qb = $this->createQueryBuilder('p');

        // Filter by file number (not encrypted)
        if (!empty($criteria['fileNumber'])) {
            $qb->andWhere('LOWER(p.fileNumber) LIKE LOWER(:fileNumber)')
               ->setParameter('fileNumber', '%' . $criteria['fileNumber'] . '%');
        }

        // Filter by city (not encrypted)
        if (!empty($criteria['city'])) {
            $qb->andWhere('LOWER(p.city) LIKE LOWER(:city)')
               ->setParameter('city', '%' . $criteria['city'] . '%');
        }

        // Get all matching patients (we need to filter encrypted fields in PHP)
        $patients = $qb->orderBy('p.lastName', 'ASC')
                       ->addOrderBy('p.firstName', 'ASC')
                       ->getQuery()
                       ->getResult();

        // Filter by encrypted fields in PHP
        if (!empty($criteria['lastName']) || !empty($criteria['firstName'])) {
            $patients = array_filter($patients, function (Patient $patient) use ($criteria) {
                $matches = true;

                if (!empty($criteria['lastName'])) {
                    $matches = $matches && str_contains(
                        mb_strtolower($patient->getLastName() ?? ''),
                        mb_strtolower($criteria['lastName'])
                    );
                }

                if (!empty($criteria['firstName'])) {
                    $matches = $matches && str_contains(
                        mb_strtolower($patient->getFirstName() ?? ''),
                        mb_strtolower($criteria['firstName'])
                    );
                }

                return $matches;
            });

            // Re-index array
            $patients = array_values($patients);
        }

        // Sort alphabetically by name (since encrypted fields aren't sorted by DB)
        usort($patients, function (Patient $a, Patient $b) {
            $lastNameCompare = strcasecmp($a->getLastName() ?? '', $b->getLastName() ?? '');
            if ($lastNameCompare !== 0) {
                return $lastNameCompare;
            }
            return strcasecmp($a->getFirstName() ?? '', $b->getFirstName() ?? '');
        });

        $total = count($patients);
        $hasMore = $total > 200;

        // Paginate
        $offset = ($page - 1) * $limit;
        $paginatedPatients = array_slice($patients, $offset, $limit);

        return [
            'patients' => $paginatedPatients,
            'total' => $total,
            'hasMore' => $hasMore,
        ];
    }

    /**
     * Count total patients matching criteria.
     */
    public function countBySearchCriteria(array $criteria): int
    {
        $result = $this->searchPaginated($criteria, 1, PHP_INT_MAX);
        return $result['total'];
    }

    /**
     * Find all patients ordered alphabetically.
     *
     * @return Patient[]
     */
    public function findAllOrdered(): array
    {
        $patients = $this->findAll();

        usort($patients, function (Patient $a, Patient $b) {
            $lastNameCompare = strcasecmp($a->getLastName() ?? '', $b->getLastName() ?? '');
            if ($lastNameCompare !== 0) {
                return $lastNameCompare;
            }
            return strcasecmp($a->getFirstName() ?? '', $b->getFirstName() ?? '');
        });

        return $patients;
    }

    /**
     * Find patient by file number.
     */
    public function findByFileNumber(string $fileNumber): ?Patient
    {
        return $this->findOneBy(['fileNumber' => $fileNumber]);
    }
}
