<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_TECH_ADMIN')]
class AuditLogController extends AbstractController
{
    #[Route('/logs', name: 'app_admin_audit_logs', methods: ['GET', 'POST'])]
    public function index(Request $request, AuditLogRepository $auditLogRepository): Response
    {
        $userFilter = null;
        $actionFilter = null;
        $entityTypeFilter = null;
        $dateFrom = null;
        $dateTo = null;
        $limit = 50;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('audit_log_filter', $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide');

                return $this->redirectToRoute('app_admin_audit_logs');
            }

            $userFilter = $request->request->get('user') ?: null;
            $actionFilter = $request->request->get('action') ?: null;
            $entityTypeFilter = $request->request->get('entityType') ?: null;

            $dateFromStr = $request->request->get('dateFrom');
            if ($dateFromStr) {
                $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $dateFromStr)
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d', $dateFromStr)
                    ?: null;
                if (null === $dateFrom) {
                    $this->addFlash('error', 'La date de début est invalide. Format attendu : JJ/MM/AAAA HH:MM');
                }
            }

            $dateToStr = $request->request->get('dateTo');
            if ($dateToStr) {
                $dateTo = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $dateToStr)
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d', $dateToStr)
                    ?: null;
                if (null === $dateTo) {
                    $this->addFlash('error', 'La date de fin est invalide. Format attendu : JJ/MM/AAAA HH:MM');
                } else {
                    // Include the full selected minute (e.g. 16:00 → 16:00:59)
                    $dateTo = $dateTo->setTime((int) $dateTo->format('H'), (int) $dateTo->format('i'), 59);
                }
            }

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $this->addFlash('error', 'La date de début ne peut pas être postérieure à la date de fin.');
                $dateFrom = null;
                $dateTo = null;
            }
        }

        $logs = $auditLogRepository->findFiltered(
            $userFilter,
            $actionFilter,
            $entityTypeFilter,
            $dateFrom,
            $dateTo,
            1,
            $limit,
        );

        $totalLogs = $auditLogRepository->countFiltered(
            $userFilter,
            $actionFilter,
            $entityTypeFilter,
            $dateFrom,
            $dateTo,
        );

        $actions = [
            AuditLog::ACTION_VIEW => 'Consultation',
            AuditLog::ACTION_CREATE => 'Création',
            AuditLog::ACTION_EDIT => 'Modification',
            AuditLog::ACTION_DELETE => 'Suppression',
            AuditLog::ACTION_SEARCH => 'Recherche',
            AuditLog::ACTION_PASSWORD_CHANGE => 'Changement MDP',
        ];

        $entityTypes = $auditLogRepository->findDistinctEntityTypes();

        return $this->render('admin/audit_logs/index.html.twig', [
            'logs' => $logs,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'totalLogs' => $totalLogs,
            'hasMore' => count($logs) < $totalLogs,
            'filters' => [
                'user' => $userFilter,
                'action' => $actionFilter,
                'entityType' => $entityTypeFilter,
                'dateFrom' => $dateFromStr ?? null,
                'dateTo' => $dateToStr ?? null,
            ],
        ]);
    }

    #[Route('/logs/more', name: 'app_admin_audit_logs_more', methods: ['POST'])]
    public function loadMore(Request $request, AuditLogRepository $auditLogRepository): JsonResponse
    {
        if (!$this->isCsrfTokenValid('audit_log_more', $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF'], Response::HTTP_FORBIDDEN);
        }

        $page = max(1, $request->request->getInt('page', 1));
        $limit = 50;

        $userFilter = $request->request->get('user') ?: null;
        $actionFilter = $request->request->get('action') ?: null;
        $entityTypeFilter = $request->request->get('entityType') ?: null;
        $dateFrom = null;
        $dateTo = null;

        $dateFromStr = $request->request->get('dateFrom');
        if ($dateFromStr) {
            $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $dateFromStr)
                ?: \DateTimeImmutable::createFromFormat('Y-m-d', $dateFromStr)
                ?: null;
            if (null === $dateFrom) {
                return new JsonResponse(['error' => 'Date de début invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        $dateToStr = $request->request->get('dateTo');
        if ($dateToStr) {
            $dateTo = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $dateToStr)
                ?: \DateTimeImmutable::createFromFormat('Y-m-d', $dateToStr)
                ?: null;
            if (null === $dateTo) {
                return new JsonResponse(['error' => 'Date de fin invalide'], Response::HTTP_BAD_REQUEST);
            } else {
                $dateTo = $dateTo->setTime((int) $dateTo->format('H'), (int) $dateTo->format('i'), 59);
            }
        }

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            return new JsonResponse(['error' => 'La date de début ne peut pas être postérieure à la date de fin'], Response::HTTP_BAD_REQUEST);
        }

        $logs = $auditLogRepository->findFiltered(
            $userFilter,
            $actionFilter,
            $entityTypeFilter,
            $dateFrom,
            $dateTo,
            $page,
            $limit,
        );

        $totalLogs = $auditLogRepository->countFiltered(
            $userFilter,
            $actionFilter,
            $entityTypeFilter,
            $dateFrom,
            $dateTo,
        );

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'createdAt' => $log->getCreatedAt()->format('d/m/Y H:i:s'),
                'userIdentifier' => $log->getUserIdentifier(),
                'userFullName' => $log->getUser()?->getFullName(),
                'userEmail' => $log->getUser()?->getEmail(),
                'action' => $log->getAction(),
                'actionLabel' => $log->getActionLabel(),
                'entityType' => $log->getEntityType(),
                'entityId' => $log->getEntityId(),
                'httpMethod' => $log->getHttpMethod(),
                'uri' => $log->getUri(),
                'ipAddress' => $log->getIpAddress(),
                'details' => $log->getDetails(),
                'changedFields' => $log->getChangedFields(),
            ];
        }

        return new JsonResponse([
            'rows' => $rows,
            'hasMore' => ($page * $limit) < $totalLogs,
        ]);
    }
}
