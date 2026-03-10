<?php

namespace App\Controller;

use App\Entity\BiologicalResult;
use App\Entity\Patient;
use App\Form\BiologicalResultType;
use App\Repository\BiologicalResultRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/resultats-biologiques', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class BiologicalResultController extends AbstractController
{
    public function __construct(
        private BiologicalResultRepository $biologicalResultRepository,
    ) {
    }

    #[Route('', name: 'app_biological_result_index')]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $results = $this->biologicalResultRepository->findByPatient($patient->getId());

        return $this->render('biological_result/index.html.twig', [
            'patient' => $patient,
            'results' => $results,
            'activeTab' => 'resultats',
        ]);
    }

    #[Route('/new', name: 'app_biological_result_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $result = new BiologicalResult();
        $result->setPatient($patient);
        $form = $this->createForm(BiologicalResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->biologicalResultRepository->save($result);
            $this->addFlash('success', 'Résultat biologique ajouté avec succès');

            return $this->redirectToRoute('app_biological_result_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('biological_result/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'activeTab' => 'resultats',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_biological_result_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, BiologicalResult $result): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(BiologicalResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->biologicalResultRepository->save($result);
            $this->addFlash('success', 'Résultat biologique modifié avec succès');

            return $this->redirectToRoute('app_biological_result_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('biological_result/edit.html.twig', [
            'patient' => $patient,
            'result' => $result,
            'form' => $form,
            'activeTab' => 'resultats',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_biological_result_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, BiologicalResult $result): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $result->getId(), $request->request->get('_token'))) {
            $this->biologicalResultRepository->remove($result);
            $this->addFlash('success', 'Résultat biologique supprimé avec succès');
        }

        return $this->redirectToRoute('app_biological_result_index', ['patientId' => $patient->getId()]);
    }
}
