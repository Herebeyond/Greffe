<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\TherapeuticEducation;
use App\Form\TherapeuticEducationType;
use App\Repository\TherapeuticEducationRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/etp', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class TherapeuticEducationController extends AbstractController
{
    public function __construct(
        private TherapeuticEducationRepository $therapeuticEducationRepository,
    ) {
    }

    #[Route('', name: 'app_therapeutic_education_index')]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $sessions = $this->therapeuticEducationRepository->findByPatient($patient->getId());

        return $this->render('therapeutic_education/index.html.twig', [
            'patient' => $patient,
            'sessions' => $sessions,
            'activeTab' => 'etp',
        ]);
    }

    #[Route('/new', name: 'app_therapeutic_education_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $session = new TherapeuticEducation();
        $session->setPatient($patient);
        $form = $this->createForm(TherapeuticEducationType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->therapeuticEducationRepository->save($session);
            $this->addFlash('success', 'Séance ETP ajoutée avec succès');

            return $this->redirectToRoute('app_therapeutic_education_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('therapeutic_education/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'activeTab' => 'etp',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_therapeutic_education_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, TherapeuticEducation $session): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(TherapeuticEducationType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->therapeuticEducationRepository->save($session);
            $this->addFlash('success', 'Séance ETP modifiée avec succès');

            return $this->redirectToRoute('app_therapeutic_education_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('therapeutic_education/edit.html.twig', [
            'patient' => $patient,
            'session' => $session,
            'form' => $form,
            'activeTab' => 'etp',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_therapeutic_education_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, TherapeuticEducation $session): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
            $this->therapeuticEducationRepository->remove($session);
            $this->addFlash('success', 'Séance ETP supprimée avec succès');
        }

        return $this->redirectToRoute('app_therapeutic_education_index', ['patientId' => $patient->getId()]);
    }
}
