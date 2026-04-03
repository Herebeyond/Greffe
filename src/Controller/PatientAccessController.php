<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\PatientAccess;
use App\Entity\User;
use App\Repository\PatientAccessRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/patients/{patientId}/access', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class PatientAccessController extends AbstractController
{
    public function __construct(
        private PatientAccessRepository $patientAccessRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
    ) {
    }

    /**
     * Show access management page for a patient.
     */
    #[Route('', name: 'app_patient_access', methods: ['GET'])]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // SUPER_ADMIN can manage access without VIEW_PATIENT
        if (!in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);
        }

        $this->assertCanManageAccess($currentUser, $patient);

        $accessRecords = $this->patientAccessRepository->findByPatient($patient);
        $availableUsers = $this->getAvailableUsers($patient);

        return $this->render('patient_access/index.html.twig', [
            'patient' => $patient,
            'accessRecords' => $accessRecords,
            'availableUsers' => $availableUsers,
        ]);
    }

    /**
     * Grant access to a practitioner.
     */
    #[Route('/grant', name: 'app_patient_access_grant', methods: ['POST'])]
    public function grant(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // SUPER_ADMIN can manage without VIEW_PATIENT
        if (!in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);
        }

        $this->assertCanManageAccess($currentUser, $patient);

        if (!$this->isCsrfTokenValid('grant_access' . $patient->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        $userId = $request->request->getInt('user_id');
        $targetUser = $this->userRepository->find($userId);

        if (!$targetUser || !$targetUser->isActive()) {
            $this->addFlash('error', 'Utilisateur invalide ou inactif');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Check target user has a medical role
        $targetRoles = $targetUser->getRoles();
        $isMedical = in_array('ROLE_DOCTOR', $targetRoles, true) || in_array('ROLE_NURSE', $targetRoles, true);
        if (!$isMedical) {
            $this->addFlash('error', 'Seuls les médecins et infirmiers peuvent recevoir un accès patient');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Check not already assigned
        if ($this->patientAccessRepository->hasAccess($targetUser, $patient)) {
            $this->addFlash('error', 'Cet utilisateur a déjà un accès à ce patient');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Determine access level: nurses always secondary, doctors secondary by default
        $access = new PatientAccess();
        $access->setPatient($patient);
        $access->setUser($targetUser);
        $access->setAccessLevel(PatientAccess::LEVEL_SECONDARY);
        $access->setGrantedBy($currentUser);
        $this->patientAccessRepository->save($access);

        $this->notificationService->notifyAccessGranted($targetUser, $currentUser, $patient, PatientAccess::LEVEL_SECONDARY);

        $this->addFlash('success', sprintf(
            'Accès accordé à %s (accès secondaire)',
            $targetUser->getFullName()
        ));

        return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
    }

    /**
     * Revoke a practitioner's access.
     */
    #[Route('/revoke/{userId}', name: 'app_patient_access_revoke', methods: ['POST'], requirements: ['userId' => '\d+'])]
    public function revoke(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, int $userId): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);
        }

        $this->assertCanManageAccess($currentUser, $patient);

        if (!$this->isCsrfTokenValid('revoke_access' . $patient->getId() . '_' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        $targetUser = $this->userRepository->find($userId);
        if (!$targetUser) {
            $this->addFlash('error', 'Utilisateur introuvable');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        $access = $this->patientAccessRepository->findAccess($targetUser, $patient);
        if (!$access) {
            $this->addFlash('error', 'Cet utilisateur n\'a pas d\'accès à ce patient');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Cannot revoke own access (except via transfer)
        if ($targetUser === $currentUser && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->addFlash('error', 'Vous ne pouvez pas révoquer votre propre accès');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Non-admin primary holders can only revoke secondary access
        if ($access->isPrimary() && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->addFlash('error', 'Seul un super-administrateur peut révoquer un accès principal. Utilisez le transfert à la place.');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        $this->patientAccessRepository->remove($access);

        $this->notificationService->notifyAccessRevoked($targetUser, $currentUser, $patient);

        $this->addFlash('success', sprintf(
            'Accès révoqué pour %s',
            $targetUser->getFullName()
        ));

        return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
    }

    /**
     * Transfer primary access to another doctor.
     * The current primary holder loses all access; the target becomes primary.
     */
    #[Route('/transfer/{userId}', name: 'app_patient_access_transfer', methods: ['POST'], requirements: ['userId' => '\d+'])]
    public function transfer(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, int $userId): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);
        }

        $this->assertCanManageAccess($currentUser, $patient);

        if (!$this->isCsrfTokenValid('transfer_access' . $patient->getId() . '_' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        $targetUser = $this->userRepository->find($userId);
        if (!$targetUser || !$targetUser->isActive()) {
            $this->addFlash('error', 'Utilisateur invalide ou inactif');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Only doctors can receive primary access
        if (!in_array('ROLE_DOCTOR', $targetUser->getRoles(), true)) {
            $this->addFlash('error', 'Seul un médecin peut recevoir l\'accès principal');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Cannot transfer to self
        if ($targetUser === $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas transférer l\'accès à vous-même');
            return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
        }

        // Find current primary access
        $currentPrimary = $this->patientAccessRepository->findPrimaryAccess($patient);

        // If SUPER_ADMIN is managing (not necessarily the primary holder)
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true);

        // Remove current primary access (the doctor who transfers loses all access)
        if ($currentPrimary) {
            $losingUser = $currentPrimary->getUser();
            $this->patientAccessRepository->remove($currentPrimary, false);
        }

        // Check if target already has secondary access — upgrade it
        $existingAccess = $this->patientAccessRepository->findAccess($targetUser, $patient);
        if ($existingAccess) {
            $existingAccess->setAccessLevel(PatientAccess::LEVEL_PRIMARY);
            $existingAccess->setGrantedBy($currentUser);
            $existingAccess->setGrantedAt(new \DateTimeImmutable());
        } else {
            $newAccess = new PatientAccess();
            $newAccess->setPatient($patient);
            $newAccess->setUser($targetUser);
            $newAccess->setAccessLevel(PatientAccess::LEVEL_PRIMARY);
            $newAccess->setGrantedBy($currentUser);
            $this->entityManager->persist($newAccess);
        }

        $this->entityManager->flush();

        // Notify the new primary holder
        $this->notificationService->notifyAccessTransferredTo($targetUser, $currentUser, $patient);

        // Notify the old primary holder (if different from current user and target)
        if (isset($losingUser)) {
            $this->notificationService->notifyAccessTransferredFrom($losingUser, $currentUser, $patient, $targetUser);
        }

        $message = sprintf('Accès principal transféré à %s', $targetUser->getFullName());
        if (isset($losingUser) && $losingUser !== $currentUser) {
            $message .= sprintf('. %s a perdu son accès.', $losingUser->getFullName());
        }

        $this->addFlash('success', $message);

        // If the current user just lost their own access, redirect to patient list
        if (!$isSuperAdmin && isset($losingUser) && $losingUser === $currentUser) {
            return $this->redirectToRoute('app_patient_index');
        }

        return $this->redirectToRoute('app_patient_access', ['patientId' => $patient->getId()]);
    }

    /**
     * Assert that the current user can manage patient access.
     */
    private function assertCanManageAccess(User $user, Patient $patient): void
    {
        // SUPER_ADMIN can always manage access
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return;
        }

        // Primary access holder can manage
        $access = $this->patientAccessRepository->findAccess($user, $patient);
        if ($access !== null && $access->isPrimary()) {
            return;
        }

        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer les accès de ce patient');
    }

    /**
     * Get active doctors and nurses who don't already have access to this patient.
     *
     * @return User[]
     */
    private function getAvailableUsers(Patient $patient): array
    {
        $allUsers = $this->userRepository->findBy(['isActive' => true]);
        $existingUserIds = array_map(
            fn(PatientAccess $pa) => $pa->getUser()->getId(),
            $this->patientAccessRepository->findByPatient($patient)
        );

        return array_filter($allUsers, function (User $user) use ($existingUserIds) {
            if (in_array($user->getId(), $existingUserIds, true)) {
                return false;
            }
            $roles = $user->getRoles();
            return in_array('ROLE_DOCTOR', $roles, true) || in_array('ROLE_NURSE', $roles, true);
        });
    }
}
