<?php

namespace App\Controller;

use App\Entity\BreakTheGlassAccess;
use App\Entity\Patient;
use App\Entity\User;
use App\Form\BreakTheGlassType;
use App\Repository\BreakTheGlassAccessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles "break-the-glass" (bris de glace) emergency access requests.
 *
 * Only healthcare professionals (ROLE_DOCTOR, ROLE_NURSE) may use this.
 * Technical administrators (ROLE_TECH_ADMIN) are never allowed.
 */
class BreakTheGlassController extends AbstractController
{
    public function __construct(
        private BreakTheGlassAccessRepository $btgRepository,
    ) {
    }

    #[Route('/break-the-glass/{patientId}', name: 'app_break_the_glass', methods: ['GET', 'POST'], requirements: ['patientId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function request(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $roles = $user->getRoles();

        // Only medical personnel can use break-the-glass
        if (!in_array('ROLE_DOCTOR', $roles, true) && !in_array('ROLE_NURSE', $roles, true)) {
            throw $this->createAccessDeniedException('Seuls les professionnels de santé peuvent utiliser le bris de glace');
        }

        // If user already has normal access, redirect to patient
        if ($patient->isAuthorizedPractitioner($user)) {
            return $this->redirectToRoute('app_patient_show', ['id' => $patient->getId()]);
        }

        // If active BTG already exists, redirect to patient
        if ($this->btgRepository->hasActiveAccess($user, $patient)) {
            $this->addFlash('info', 'Vous avez déjà un accès d\'urgence actif pour ce patient');
            return $this->redirectToRoute('app_patient_show', ['id' => $patient->getId()]);
        }

        $form = $this->createForm(BreakTheGlassType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $btgAccess = new BreakTheGlassAccess();
            $btgAccess->setUser($user);
            $btgAccess->setPatient($patient);
            $btgAccess->setJustification($form->get('justification')->getData());

            $this->btgRepository->save($btgAccess);

            $this->addFlash('warning', sprintf(
                'Accès d\'urgence accordé pour %d heures. Cet accès est journalisé et sera audité.',
                intdiv(BreakTheGlassAccess::DEFAULT_DURATION_MINUTES, 60)
            ));

            return $this->redirectToRoute('app_patient_show', ['id' => $patient->getId()]);
        }

        return $this->render('break_the_glass/request.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'duration' => BreakTheGlassAccess::DEFAULT_DURATION_MINUTES,
        ]);
    }
}
