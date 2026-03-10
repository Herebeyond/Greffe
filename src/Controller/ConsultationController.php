<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Form\ConsultationType;
use App\Repository\ConsultationRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/consultations', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class ConsultationController extends AbstractController
{
    public function __construct(
        private ConsultationRepository $consultationRepository,
    ) {
    }

    #[Route('', name: 'app_consultation_index')]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $consultations = $this->consultationRepository->findByPatient($patient->getId());

        return $this->render('consultation/index.html.twig', [
            'patient' => $patient,
            'consultations' => $consultations,
            'activeTab' => 'consultations',
        ]);
    }

    #[Route('/new', name: 'app_consultation_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $consultation = new Consultation();
        $consultation->setPatient($patient);
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->consultationRepository->save($consultation);
            $this->addFlash('success', 'Consultation ajoutée avec succès');

            return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('consultation/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'activeTab' => 'consultations',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_consultation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Consultation $consultation): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->consultationRepository->save($consultation);
            $this->addFlash('success', 'Consultation modifiée avec succès');

            return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('consultation/edit.html.twig', [
            'patient' => $patient,
            'consultation' => $consultation,
            'form' => $form,
            'activeTab' => 'consultations',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_consultation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Consultation $consultation): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $consultation->getId(), $request->request->get('_token'))) {
            $this->consultationRepository->remove($consultation);
            $this->addFlash('success', 'Consultation supprimée avec succès');
        }

        return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
    }
}
