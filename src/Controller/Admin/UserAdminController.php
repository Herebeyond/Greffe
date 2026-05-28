<?php

namespace App\Controller\Admin;

use App\Entity\PasswordHistory;
use App\Entity\User;
use App\Form\UserType;
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
            // ROLE_TECH_ADMIN can only create ROLE_USER profiles (no privileged roles)
            if (!$isSuperAdmin) {
                $user->setRoles([]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setPasswordChangedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

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
    ): Response {
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        // ROLE_TECH_ADMIN cannot edit other admin accounts
        if (!$isSuperAdmin && $this->hasAdminRole($user)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cet utilisateur');

            return $this->redirectToRoute('app_admin_users');
        }

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
            'is_super_admin' => $isSuperAdmin,
            'show_is_active' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ROLE_TECH_ADMIN has no role field in the form, so existing roles are preserved.

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                // Record old password in history
                $passwordHistoryService->recordPasswordChange($user, PasswordHistory::REASON_ADMIN_CHANGE);

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->setPasswordChangedAt(new \DateTimeImmutable());
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Prevent self-deletion
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');

                return $this->redirectToRoute('app_admin_users');
            }

            // ROLE_TECH_ADMIN cannot delete admin accounts
            if (!$this->isGranted('ROLE_SUPER_ADMIN') && $this->hasAdminRole($user)) {
                $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer cet utilisateur');

                return $this->redirectToRoute('app_admin_users');
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/toggle-active', name: 'app_admin_users_toggle_active', methods: ['POST'])]
    public function toggleActive(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_active' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte');

            return $this->redirectToRoute('app_admin_users');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $this->hasAdminRole($user)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier le statut de cet utilisateur');

            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

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
}
