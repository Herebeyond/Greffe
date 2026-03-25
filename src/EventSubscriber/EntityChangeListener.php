<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Service\EncryptionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Captures entity field changes on update and records them in the audit log.
 *
 * - changedFields: plain list of field names (visible to tech admins in audit viewer)
 * - encryptedChanges: encrypted JSON of old→new values (stored for legal audit, never displayed)
 */
#[AsDoctrineListener(event: Events::onFlush)]
class EntityChangeListener
{
    /** Entity classes that should be tracked for field-level changes. */
    private const TRACKED_ENTITIES = [
        'App\Entity\Patient' => 'Patient',
        'App\Entity\Consultation' => 'Consultation',
        'App\Entity\BiologicalResult' => 'BiologicalResult',
        'App\Entity\MedicalHistory' => 'MedicalHistory',
        'App\Entity\TherapeuticEducation' => 'TherapeuticEducation',
        'App\Entity\Transplant' => 'Transplant',
        'App\Entity\Donor' => 'Donor',
    ];

    public function __construct(
        private Security $security,
        private EncryptionService $encryptionService,
        private RequestStack $requestStack,
        private AuditLogRepository $auditLogRepository,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $className = get_class($entity);
            if (!isset(self::TRACKED_ENTITIES[$className])) {
                continue;
            }

            $changeset = $uow->getEntityChangeSet($entity);
            if (empty($changeset)) {
                continue;
            }

            $entityType = self::TRACKED_ENTITIES[$className];
            $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

            $changedFields = array_keys($changeset);

            // Build the detailed changes (old → new) for encrypted storage
            $changeDetails = [];
            foreach ($changeset as $field => [$oldValue, $newValue]) {
                $changeDetails[$field] = [
                    'old' => $this->normalizeValue($oldValue),
                    'new' => $this->normalizeValue($newValue),
                ];
            }

            $encryptedChanges = $this->encryptionService->encrypt(
                json_encode($changeDetails, JSON_UNESCAPED_UNICODE)
            );

            $request = $this->requestStack->getCurrentRequest();

            $log = new AuditLog();
            $log->setUser($user);
            $log->setUserIdentifier($user->getUserIdentifier());
            $log->setAction(AuditLog::ACTION_EDIT);
            $log->setEntityType($entityType);
            $log->setEntityId($entityId);
            $log->setRouteName($request?->attributes->get('_route') ?? 'unknown');
            $log->setHttpMethod($request?->getMethod() ?? 'POST');
            $log->setUri(mb_substr($request?->getPathInfo() ?? '', 0, 2048));
            $log->setIpAddress($request?->getClientIp());
            $log->setChangedFields($changedFields);
            $log->setEncryptedChanges($encryptedChanges);
            $log->setDetails('Champs modifiés : ' . implode(', ', $changedFields));

            $em->persist($log);
            $classMetadata = $em->getClassMetadata(AuditLog::class);
            $uow->computeChangeSet($classMetadata, $log);
        }
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            if (method_exists($value, 'getId')) {
                return $value->getId();
            }

            return get_class($value);
        }

        return $value;
    }
}
