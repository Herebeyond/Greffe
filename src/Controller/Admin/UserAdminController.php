<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Entity\PasswordHistory;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Service\PasswordHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_TECH_ADMIN')]
class UserAdminController extends AbstractController
{
    /**
     * Admin roles that only ROLE_SUPER_ADMIN can manage.
     */
    private const ADMIN_ROLES = [
        'ROLE_SUPER_ADMIN',
        'ROLE_TECH_ADMIN',
    ];

    #[Route('/users', name: 'app_admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'app_admin_users_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AuditLogRepository $auditLogRepository,
    ): Response {
        $user = new User();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => true,
            'is_super_admin' => $isSuperAdmin,
            'show_is_active' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedRoles = $form->has('roles') ? (array) $form->get('roles')->getData() : [];
            if (!$isSuperAdmin) {
                $submittedRoles = array_values(array_diff($submittedRoles, self::ADMIN_ROLES));
            }
            $user->setRoles($submittedRoles);

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setPasswordChangedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_CREATE,
                $user,
                sprintf(
                    'Création utilisateur cible=%s roles=[%s] actif=%s',
                    $this->formatUserTarget($user),
                    implode(', ', $this->normalizeRolesForAudit($user->getRoles())),
                    $user->isActive() ? 'oui' : 'non'
                ),
                ['name', 'surname', 'email', 'cristalId', 'roles', 'isActive', 'password']
            );

            $this->addFlash('success', 'Utilisateur créé avec succès');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordHistoryService $passwordHistoryService,
        AuditLogRepository $auditLogRepository,
    ): Response {
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isSelfEdit = $currentUser instanceof User && $currentUser->getId() === $user->getId();

        $originalName = $user->getName();
        $originalSurname = $user->getSurname();
        $originalEmail = $user->getEmail();
        $originalCristalId = $user->getCristalId();
        $originalRoles = $user->getRoles();
        $originalIsActive = $user->isActive();

        // ROLE_TECH_ADMIN cannot edit other admin accounts
        if (!$isSuperAdmin && $this->hasAdminRole($user)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cet utilisateur');

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_EDIT,
                $user,
                'Tentative refusée de modification utilisateur cible=' . $this->formatUserTarget($user)
            );

            return $this->redirectToRoute('app_admin_users');
        }

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
            'is_super_admin' => $isSuperAdmin,
            'show_is_active' => true,
            'is_self_edit' => $isSelfEdit,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $changedFields = [];

            if ($isSelfEdit) {
                // Never allow changing own roles/activation from admin user management.
                $user->setRoles($originalRoles);
                $user->setIsActive($originalIsActive);
            } else {
                $submittedRoles = $form->has('roles') ? (array) $form->get('roles')->getData() : [];
                if (!$isSuperAdmin) {
                    $submittedRoles = array_values(array_diff($submittedRoles, self::ADMIN_ROLES));
                }
                $user->setRoles($submittedRoles);
            }

            if ($originalName !== $user->getName()) {
                $changedFields[] = 'name';
            }
            if ($originalSurname !== $user->getSurname()) {
                $changedFields[] = 'surname';
            }
            if ($originalEmail !== $user->getEmail()) {
                $changedFields[] = 'email';
            }
            if ($originalCristalId !== $user->getCristalId()) {
                $changedFields[] = 'cristalId';
            }
            if (!$isSelfEdit && $this->normalizeRolesForAudit($originalRoles) !== $this->normalizeRolesForAudit($user->getRoles())) {
                $changedFields[] = 'roles';
            }
            if (!$isSelfEdit && $originalIsActive !== $user->isActive()) {
                $changedFields[] = 'isActive';
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                // Record old password in history
                $passwordHistoryService->recordPasswordChange($user, PasswordHistory::REASON_ADMIN_CHANGE);

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->setPasswordChangedAt(new \DateTimeImmutable());
                $changedFields[] = 'password';
            }

            $entityManager->flush();

            if (!empty($changedFields)) {
                $this->logAdminUserAction(
                    $auditLogRepository,
                    $request,
                    AuditLog::ACTION_EDIT,
                    $user,
                    sprintf(
                        'Modification utilisateur cible=%s champs=[%s]',
                        $this->formatUserTarget($user),
                        implode(', ', $changedFields)
                    ),
                    $changedFields
                );
            }

            $this->addFlash('success', 'Utilisateur modifié avec succès');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'is_self_edit' => $isSelfEdit,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogRepository $auditLogRepository,
    ): Response {
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Prevent self-deletion
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');

                $this->logAdminUserAction(
                    $auditLogRepository,
                    $request,
                    AuditLog::ACTION_DELETE,
                    $user,
                    'Tentative refusée de suppression de son propre compte cible=' . $this->formatUserTarget($user)
                );

                return $this->redirectToRoute('app_admin_users');
            }

            // ROLE_TECH_ADMIN cannot delete admin accounts
            if (!$this->isGranted('ROLE_SUPER_ADMIN') && $this->hasAdminRole($user)) {
                $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer cet utilisateur');

                $this->logAdminUserAction(
                    $auditLogRepository,
                    $request,
                    AuditLog::ACTION_DELETE,
                    $user,
                    'Tentative refusée de suppression utilisateur admin cible=' . $this->formatUserTarget($user)
                );

                return $this->redirectToRoute('app_admin_users');
            }

            $targetSummary = $this->formatUserTarget($user);

            $entityManager->remove($user);
            $entityManager->flush();

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_DELETE,
                null,
                'Suppression utilisateur cible=' . $targetSummary,
                ['user']
            );

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/toggle-active', name: 'app_admin_users_toggle_active', methods: ['POST'])]
    public function toggleActive(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogRepository $auditLogRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_active' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_EDIT,
                $user,
                'Tentative refusée de changement statut (CSRF invalide) cible=' . $this->formatUserTarget($user)
            );

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte');

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_EDIT,
                $user,
                'Tentative refusée de changement statut de son propre compte cible=' . $this->formatUserTarget($user)
            );

            return $this->redirectToRoute('app_admin_users');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $this->hasAdminRole($user)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier le statut de cet utilisateur');

            $this->logAdminUserAction(
                $auditLogRepository,
                $request,
                AuditLog::ACTION_EDIT,
                $user,
                'Tentative refusée de changement statut d\'un admin cible=' . $this->formatUserTarget($user)
            );

            return $this->redirectToRoute('app_admin_users');
        }

        $oldIsActive = $user->isActive();
        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->logAdminUserAction(
            $auditLogRepository,
            $request,
            AuditLog::ACTION_EDIT,
            $user,
            sprintf(
                'Changement statut utilisateur cible=%s ancien=%s nouveau=%s',
                $this->formatUserTarget($user),
                $oldIsActive ? 'actif' : 'désactivé',
                $user->isActive() ? 'actif' : 'désactivé'
            ),
            ['isActive']
        );

        $this->addFlash('success', $user->isActive()
            ? 'Compte utilisateur réactivé avec succès'
            : 'Compte utilisateur désactivé avec succès'
        );

        return $this->redirectToRoute('app_admin_users');
    }

    /**
     * Check if a user holds any admin role that only ROLE_SUPER_ADMIN can manage.
     */
    private function hasAdminRole(User $user): bool
    {
        return !empty(array_intersect($user->getRoles(), self::ADMIN_ROLES));
    }

    private function logAdminUserAction(
        AuditLogRepository $auditLogRepository,
        Request $request,
        string $action,
        ?User $targetUser,
        string $details,
        array $changedFields = [],
    ): void {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return;
        }

        $log = new AuditLog();
        $log->setUser($actor);
        $log->setUserIdentifier($actor->getUserIdentifier());
        $log->setAction($action);
        $log->setEntityType('User');
        $log->setEntityId($targetUser?->getId());
        $log->setRouteName((string) $request->attributes->get('_route', 'app_admin_users'));
        $log->setHttpMethod($request->getMethod());
        $log->setUri(mb_substr($request->getPathInfo(), 0, 2048));
        $log->setIpAddress($request->getClientIp());
        $log->setDetails($details);
        $log->setChangedFields($changedFields !== [] ? $changedFields : null);

        $auditLogRepository->save($log);
    }

    private function formatUserTarget(User $user): string
    {
        return sprintf(
            '%s (id=%d, email=%s)',
            $user->getFullName(),
            (int) $user->getId(),
            (string) $user->getEmail()
        );
    }

    /**
     * @param array<int, string> $roles
     * @return array<int, string>
     */
    private function normalizeRolesForAudit(array $roles): array
    {
        $roles = array_values(array_unique($roles));
        sort($roles);

        return $roles;
    }
}
