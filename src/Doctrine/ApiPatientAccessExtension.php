<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\BiologicalResult;
use App\Entity\Consultation;
use App\Entity\MedicalHistory;
use App\Entity\Notification;
use App\Entity\Patient;
use App\Entity\TherapeuticEducation;
use App\Entity\Transplant;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Filters API collection results based on user access rights.
 *
 * - Patient: only patients the user has access to (via patient_access table)
 * - Patient sub-entities: only those belonging to accessible patients
 * - Notification: only notifications for the current user
 */
class ApiPatientAccessExtension implements QueryCollectionExtensionInterface
{
    private const PATIENT_SUB_ENTITIES = [
        Consultation::class,
        BiologicalResult::class,
        MedicalHistory::class,
        TherapeuticEducation::class,
        Transplant::class,
    ];

    public function __construct(
        private Security $security,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($resourceClass === Patient::class) {
            $this->filterPatients($queryBuilder, $queryNameGenerator, $user);
            return;
        }

        if (in_array($resourceClass, self::PATIENT_SUB_ENTITIES, true)) {
            $this->filterByPatientAccess($queryBuilder, $queryNameGenerator, $user);
            return;
        }

        if ($resourceClass === Notification::class) {
            $this->filterNotifications($queryBuilder, $queryNameGenerator, $user);
            return;
        }
    }

    private function filterPatients(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $paramName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin(sprintf('%s.patientAccesses', $rootAlias), 'pa')
            ->andWhere(sprintf('pa.user = :%s', $paramName))
            ->setParameter($paramName, $user);
    }

    private function filterByPatientAccess(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $paramName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin(sprintf('%s.patient', $rootAlias), 'p')
            ->innerJoin('p.patientAccesses', 'pa')
            ->andWhere(sprintf('pa.user = :%s', $paramName))
            ->setParameter($paramName, $user);
    }

    private function filterNotifications(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $paramName = $queryNameGenerator->generateParameterName('recipient');

        $queryBuilder
            ->andWhere(sprintf('%s.recipient = :%s', $rootAlias, $paramName))
            ->setParameter($paramName, $user);
    }
}
