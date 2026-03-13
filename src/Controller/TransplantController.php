<?php

namespace App\Controller;

use App\Entity\Transplant;
use App\Entity\Patient;
use App\Form\TransplantType;
use App\Form\DonorDataType;
use App\Repository\TransplantRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/transplants', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class TransplantController extends AbstractController
{
    public function __construct(
        private TransplantRepository $transplantRepository,
    ) {
    }

    #[Route('', name: 'app_transplant_index')]
    public function index(#[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $transplants = $this->transplantRepository->findByPatient($patient->getId());

        return $this->render('transplant/index.html.twig', [
            'patient' => $patient,
            'transplants' => $transplants,
            'activeTab' => 'greffes',
        ]);
    }

    #[Route('/{id}', name: 'app_transplant_show', requirements: ['id' => '\d+'])]
    public function show(#[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        return $this->render('transplant/show.html.twig', [
            'patient' => $patient,
            'transplant' => $transplant,
            'activeTab' => 'greffes',
        ]);
    }

    #[Route('/new', name: 'app_transplant_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $transplant = new Transplant();
        $transplant->setPatient($patient);

        $form = $this->createForm(TransplantType::class, $transplant);
        $form->handleRequest($request);

        // Determine donor type for the donor sub-form
        $donorType = $request->request->all('transplant')['donorType'] ?? $transplant->getDonorType();
        $donorForm = null;

        if ($donorType) {
            $donorForm = $this->createForm(DonorDataType::class, $transplant->getDonorData(), [
                'donor_type' => $donorType,
            ]);
            $donorForm->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($donorForm && $donorForm->isSubmitted() && $donorForm->isValid()) {
                $transplant->setDonorData($donorForm->getData());
            }
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Greffe ajoutée avec succès');

            return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('transplant/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'donorForm' => $donorForm,
            'donorType' => $donorType,
            'activeTab' => 'greffes',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transplant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(TransplantType::class, $transplant);
        $form->handleRequest($request);

        $donorType = $request->request->all('transplant')['donorType'] ?? $transplant->getDonorType();
        $donorForm = null;

        if ($donorType) {
            $donorForm = $this->createForm(DonorDataType::class, $transplant->getDonorData(), [
                'donor_type' => $donorType,
            ]);
            $donorForm->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($donorForm && $donorForm->isSubmitted() && $donorForm->isValid()) {
                $transplant->setDonorData($donorForm->getData());
            }
            $transplant->setUpdatedAt(new \DateTimeImmutable());
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Greffe modifiée avec succès');

            return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('transplant/edit.html.twig', [
            'patient' => $patient,
            'transplant' => $transplant,
            'form' => $form,
            'donorForm' => $donorForm,
            'donorType' => $donorType,
            'activeTab' => 'greffes',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_transplant_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $transplant->getId(), $request->request->get('_token'))) {
            $this->transplantRepository->remove($transplant);
            $this->addFlash('success', 'Greffe supprimée avec succès');
        }

        return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
    }
}
