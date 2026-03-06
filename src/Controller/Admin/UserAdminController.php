<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
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
        $form = $this->createForm(UserType::class, $user, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['require_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
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

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
