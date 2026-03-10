<?php

namespace App\Controller;

use App\Entity\MedicalHistory;
use App\Entity\Patient;
use App\Form\MedicalHistoryType;
use App\Repository\MedicalHistoryRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/antecedents', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class MedicalHistoryController extends AbstractController
{
    public function __construct(
        private MedicalHistoryRepository $medicalHistoryRepository,
    ) {
    }

    #[Route('', name: 'app_medical_history_index')]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $histories = $this->medicalHistoryRepository->findByPatient($patient->getId());

        return $this->render('medical_history/index.html.twig', [
            'patient' => $patient,
            'histories' => $histories,
            'activeTab' => 'antecedents',
        ]);
    }

    #[Route('/new', name: 'app_medical_history_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $history = new MedicalHistory();
        $history->setPatient($patient);
        $form = $this->createForm(MedicalHistoryType::class, $history);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->medicalHistoryRepository->save($history);
            $this->addFlash('success', 'Antécédent ajouté avec succès');

            return $this->redirectToRoute('app_medical_history_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('medical_history/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'activeTab' => 'antecedents',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_medical_history_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, MedicalHistory $history): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(MedicalHistoryType::class, $history);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->medicalHistoryRepository->save($history);
            $this->addFlash('success', 'Antécédent modifié avec succès');

            return $this->redirectToRoute('app_medical_history_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('medical_history/edit.html.twig', [
            'patient' => $patient,
            'history' => $history,
            'form' => $form,
            'activeTab' => 'antecedents',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_medical_history_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, MedicalHistory $history): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $history->getId(), $request->request->get('_token'))) {
            $this->medicalHistoryRepository->remove($history);
            $this->addFlash('success', 'Antécédent supprimé avec succès');
        }

        return $this->redirectToRoute('app_medical_history_index', ['patientId' => $patient->getId()]);
    }
}
