<?php

namespace App\Controller;

use App\Entity\PasswordHistory;
use App\Entity\User;
use App\Form\PasswordChangeType;
use App\Service\PasswordHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(PasswordHistoryService $passwordHistoryService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $lastPasswordChange = $user->getPasswordChangedAt()
            ?? $passwordHistoryService->getLastPasswordChangeDate($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'lastPasswordChange' => $lastPasswordChange,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordHistoryService $passwordHistoryService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(PasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();

            // Check password is not reused
            if ($passwordHistoryService->isPasswordReused($user, $newPassword)) {
                $this->addFlash('error',
                    'Ce mot de passe a déjà été utilisé. Veuillez en choisir un nouveau.'
                );

                return $this->render('profile/change_password.html.twig', [
                    'form' => $form,
                ]);
            }

            // Record old password in history
            $passwordHistoryService->recordPasswordChange($user, PasswordHistory::REASON_SELF_CHANGE);

            // Set new password
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setPasswordChangedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}
