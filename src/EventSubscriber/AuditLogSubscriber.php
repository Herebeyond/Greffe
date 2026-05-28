<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuditLogSubscriber implements EventSubscriberInterface
{
    /**
     * Routes with dedicated detailed logging elsewhere (to avoid duplicates).
     */
    private const CUSTOM_AUDIT_ROUTES = [
        'app_admin_users_new',
        'app_admin_users_edit',
        'app_admin_users_delete',
        'app_admin_users_toggle_active',
    ];

    /**
     * Maps route names to [action, entityType].
     * Routes not in this map are ignored.
     */
    private const ROUTE_MAP = [
        // Patients
        'app_patient_index' => [null, 'Patient'], // action depends on method
        'app_patient_show' => [AuditLog::ACTION_VIEW, 'Patient'],
        'app_patient_new' => [AuditLog::ACTION_CREATE, 'Patient'],
        'app_patient_edit' => [AuditLog::ACTION_EDIT, 'Patient'],
        'app_patient_delete' => [AuditLog::ACTION_DELETE, 'Patient'],

        // Consultations
        'app_consultation_index' => [AuditLog::ACTION_VIEW, 'Consultation'],
        'app_consultation_new' => [AuditLog::ACTION_CREATE, 'Consultation'],
        'app_consultation_edit' => [AuditLog::ACTION_EDIT, 'Consultation'],
        'app_consultation_delete' => [AuditLog::ACTION_DELETE, 'Consultation'],

        // Biological results
        'app_biological_result_index' => [AuditLog::ACTION_VIEW, 'BiologicalResult'],
        'app_biological_result_new' => [AuditLog::ACTION_CREATE, 'BiologicalResult'],
        'app_biological_result_edit' => [AuditLog::ACTION_EDIT, 'BiologicalResult'],
        'app_biological_result_delete' => [AuditLog::ACTION_DELETE, 'BiologicalResult'],

        // Medical history
        'app_medical_history_index' => [AuditLog::ACTION_VIEW, 'MedicalHistory'],
        'app_medical_history_new' => [AuditLog::ACTION_CREATE, 'MedicalHistory'],
        'app_medical_history_edit' => [AuditLog::ACTION_EDIT, 'MedicalHistory'],
        'app_medical_history_delete' => [AuditLog::ACTION_DELETE, 'MedicalHistory'],

        // Therapeutic education
        'app_therapeutic_education_index' => [AuditLog::ACTION_VIEW, 'TherapeuticEducation'],
        'app_therapeutic_education_new' => [AuditLog::ACTION_CREATE, 'TherapeuticEducation'],
        'app_therapeutic_education_edit' => [AuditLog::ACTION_EDIT, 'TherapeuticEducation'],
        'app_therapeutic_education_delete' => [AuditLog::ACTION_DELETE, 'TherapeuticEducation'],

        // Transplants
        'app_transplant_index' => [AuditLog::ACTION_VIEW, 'Transplant'],
        'app_transplant_show' => [AuditLog::ACTION_VIEW, 'Transplant'],
        'app_transplant_new' => [AuditLog::ACTION_CREATE, 'Transplant'],
        'app_transplant_edit' => [AuditLog::ACTION_EDIT, 'Transplant'],
        'app_transplant_delete' => [AuditLog::ACTION_DELETE, 'Transplant'],

        // Donors
        'app_donor_index' => [null, 'Donor'], // action depends on method
        'app_donor_show' => [AuditLog::ACTION_VIEW, 'Donor'],
        'app_donor_new' => [AuditLog::ACTION_CREATE, 'Donor'],
        'app_donor_edit' => [AuditLog::ACTION_EDIT, 'Donor'],
        'app_donor_delete' => [AuditLog::ACTION_DELETE, 'Donor'],

        // Admin - Users
        'app_admin_users' => [AuditLog::ACTION_VIEW, 'User'],

        // Profile
        'app_profile' => [AuditLog::ACTION_VIEW, 'Profile'],
        'app_profile_change_password' => [AuditLog::ACTION_PASSWORD_CHANGE, null],

        // Break-the-glass
        'app_break_the_glass' => [AuditLog::ACTION_BREAK_THE_GLASS, 'Patient'],

        // Patient access management
        'app_patient_access' => [AuditLog::ACTION_VIEW, 'PatientAccess'],
        'app_patient_access_grant' => [AuditLog::ACTION_CREATE, 'PatientAccess'],
        'app_patient_access_revoke' => [AuditLog::ACTION_DELETE, 'PatientAccess'],
        'app_patient_access_transfer' => [AuditLog::ACTION_EDIT, 'PatientAccess'],

        // Notifications
        'app_notification_index' => [AuditLog::ACTION_VIEW, 'Notification'],
        'app_notification_read' => [AuditLog::ACTION_EDIT, 'Notification'],
        'app_notification_read_all' => [AuditLog::ACTION_EDIT, 'Notification'],
    ];

    public function __construct(
        private AuditLogRepository $auditLogRepository,
        private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (!$routeName) {
            return;
        }

        if (in_array($routeName, self::CUSTOM_AUDIT_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $mappedRoute = isset(self::ROUTE_MAP[$routeName]);
        $action = null;
        $entityType = null;

        if ($mappedRoute) {
            [$action, $entityType] = self::ROUTE_MAP[$routeName];
        } else {
            $action = $this->resolveGenericAction($request);
        }

        // Only log POST for create/edit/delete (not GET form displays)
        if ($mappedRoute && $this->isFormDisplayOnly($routeName, $request)) {
            return;
        }

        // Special case: patient index with POST = search, GET = page view (skip)
        if ($routeName === 'app_patient_index') {
            if ($request->isMethod('POST')) {
                $action = AuditLog::ACTION_SEARCH;
            } else {
                return;
            }
        }

        // Special case: donor index with POST = search, GET = page view (skip)
        if ($routeName === 'app_donor_index') {
            if ($request->isMethod('POST')) {
                $action = AuditLog::ACTION_SEARCH;
            } else {
                return;
            }
        }

        // Only log password change on POST
        if ($routeName === 'app_profile_change_password' && !$request->isMethod('POST')) {
            return;
        }

        $entityId = $request->attributes->getInt('id') ?: $request->attributes->getInt('patientId') ?: null;

        $details = $this->buildDetails($routeName, $request);
        if (!$mappedRoute) {
            $details = trim('Action générique route=' . $routeName . ($details ? ' | ' . $details : ''));
        }

        $log = new AuditLog();
        $log->setUser($user);
        $log->setUserIdentifier($user->getUserIdentifier());
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setRouteName($routeName);
        $log->setHttpMethod($request->getMethod());
        $log->setUri($this->sanitizeUri($request));
        $log->setIpAddress($request->getClientIp());
        $log->setDetails($details);

        $this->auditLogRepository->save($log);
    }

    private function resolveGenericAction(Request $request): string
    {
        return match ($request->getMethod()) {
            'GET', 'HEAD' => AuditLog::ACTION_VIEW,
            'POST', 'PUT', 'PATCH' => AuditLog::ACTION_EDIT,
            'DELETE' => AuditLog::ACTION_DELETE,
            default => AuditLog::ACTION_VIEW,
        };
    }

    private function isFormDisplayOnly(string $routeName, Request $request): bool
    {
        // For routes that accept GET+POST, only log on POST (actual submission)
        $postOnlyActions = ['_new', '_delete'];
        foreach ($postOnlyActions as $suffix) {
            if (str_ends_with($routeName, $suffix) && !$request->isMethod('POST')) {
                return true;
            }
        }

        // Edit routes on POST are handled by EntityChangeListener (with field-level tracking)
        // Only log GET (form display) is skipped for edit routes too
        if (str_ends_with($routeName, '_edit')) {
            return true;
        }

        return false;
    }

    private function buildDetails(string $routeName, Request $request): ?string
    {
        // For search, log which criteria were used (not their values, except fileNumber)
        if ($routeName === 'app_patient_index' && $request->isMethod('POST')) {
            $criteria = [];
            foreach (['lastName', 'firstName', 'fileNumber', 'city'] as $field) {
                $value = $request->request->get($field);
                if ($value) {
                    if ($field === 'fileNumber') {
                        $criteria[] = $field . '="' . mb_substr($value, 0, 50) . '"';
                    } else {
                        $criteria[] = $field;
                    }
                }
            }

            return $criteria ? 'Critères: ' . implode(', ', $criteria) : null;
        }

        // Donor search criteria (non-personal data, safe to log values)
        if ($routeName === 'app_donor_index' && $request->isMethod('POST')) {
            $criteria = [];
            foreach (['cristalNumber', 'bloodGroup', 'donorType'] as $field) {
                $value = $request->request->get($field);
                if ($value) {
                    $criteria[] = $field . '="' . mb_substr($value, 0, 50) . '"';
                }
            }

            return $criteria ? 'Critères: ' . implode(', ', $criteria) : null;
        }

        $patientId = $request->attributes->get('patientId');
        if ($patientId) {
            return 'Patient ID: ' . $patientId;
        }

        return null;
    }

    private function sanitizeUri(Request $request): string
    {
        $uri = $request->getPathInfo();

        return mb_substr($uri, 0, 2048);
    }
}
