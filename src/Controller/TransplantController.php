<?php

namespace App\Controller;

use App\Entity\Transplant;
use App\Entity\TransplantHlaIncompatibility;
use App\Entity\TransplantVirologicalStatus;
use App\Entity\Patient;
use App\Form\TransplantType;
use App\Form\DonorDataType;
use App\Repository\TransplantRepository;
use App\Repository\Reference\HlaLocusRepository;
use App\Repository\Reference\VirologicalMarkerRepository;
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
        private HlaLocusRepository $hlaLocusRepository,
        private VirologicalMarkerRepository $virologicalMarkerRepository,
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
        $donorTypeCode = $request->request->all('transplant')['donorType'] ?? $transplant->getDonorType()?->getCode();
        $donorForm = null;

        if ($donorTypeCode) {
            $donorForm = $this->createForm(DonorDataType::class, $transplant->getDonorData(), [
                'donor_type' => $donorTypeCode,
            ]);
            $donorForm->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($donorForm && $donorForm->isSubmitted() && $donorForm->isValid()) {
                $transplant->setDonorData($donorForm->getData());
            }
            $this->handleHlaIncompatibilities($form, $transplant);
            $this->handleVirologicalStatuses($form, $transplant);
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Greffe ajoutée avec succès');

            return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('transplant/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'donorForm' => $donorForm,
            'donorType' => $donorTypeCode,
            'activeTab' => 'greffes',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transplant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $form = $this->createForm(TransplantType::class, $transplant);

        // Pre-fill unmapped HLA and virological fields from junction entities
        $this->prefillHlaFields($form, $transplant);
        $this->prefillVirologicalFields($form, $transplant);

        $form->handleRequest($request);

        $donorTypeCode = $request->request->all('transplant')['donorType'] ?? $transplant->getDonorType()?->getCode();
        $donorForm = null;

        if ($donorTypeCode) {
            $donorForm = $this->createForm(DonorDataType::class, $transplant->getDonorData(), [
                'donor_type' => $donorTypeCode,
            ]);
            $donorForm->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($donorForm && $donorForm->isSubmitted() && $donorForm->isValid()) {
                $transplant->setDonorData($donorForm->getData());
            }
            $this->handleHlaIncompatibilities($form, $transplant);
            $this->handleVirologicalStatuses($form, $transplant);
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
            'donorType' => $donorTypeCode,
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

    private function handleHlaIncompatibilities($form, Transplant $transplant): void
    {
        $hlaCodes = ['A', 'B', 'Cw', 'DR', 'DQ', 'DP'];

        // Index existing incompatibilities by locus code for in-place updates
        $existingByCode = [];
        foreach ($transplant->getHlaIncompatibilities() as $incompat) {
            $existingByCode[$incompat->getHlaLocus()->getCode()] = $incompat;
        }

        foreach ($hlaCodes as $code) {
            $fieldName = 'hla' . $code;
            $value = $form->get($fieldName)->getData();
            if ($value !== null) {
                if (isset($existingByCode[$code])) {
                    // Update existing record in-place
                    $existingByCode[$code]->setIncompatibilityCount((int) $value);
                    unset($existingByCode[$code]);
                } else {
                    // Create new record
                    $locus = $this->hlaLocusRepository->findOneByCode($code);
                    if ($locus) {
                        $incompat = new TransplantHlaIncompatibility();
                        $incompat->setTransplant($transplant);
                        $incompat->setHlaLocus($locus);
                        $incompat->setIncompatibilityCount((int) $value);
                        $transplant->addHlaIncompatibility($incompat);
                    }
                }
            } elseif (isset($existingByCode[$code])) {
                // Value cleared — remove the record
                $transplant->removeHlaIncompatibility($existingByCode[$code]);
                unset($existingByCode[$code]);
            }
        }
    }

    private function handleVirologicalStatuses($form, Transplant $transplant): void
    {
        $virologicalMap = [
            'cmvStatus' => 'CMV',
            'ebvStatus' => 'EBV',
            'toxoplasmosisStatus' => 'toxoplasmosis',
        ];

        // Index existing statuses by marker code for in-place updates
        $existingByCode = [];
        foreach ($transplant->getVirologicalStatuses() as $status) {
            $existingByCode[$status->getVirologicalMarker()->getCode()] = $status;
        }

        foreach ($virologicalMap as $fieldName => $markerCode) {
            $value = $form->get($fieldName)->getData();
            if ($value !== null) {
                if (isset($existingByCode[$markerCode])) {
                    // Update existing record in-place
                    $existingByCode[$markerCode]->setStatus($value);
                    unset($existingByCode[$markerCode]);
                } else {
                    // Create new record
                    $marker = $this->virologicalMarkerRepository->findOneByCode($markerCode);
                    if ($marker) {
                        $viroStatus = new TransplantVirologicalStatus();
                        $viroStatus->setTransplant($transplant);
                        $viroStatus->setVirologicalMarker($marker);
                        $viroStatus->setStatus($value);
                        $transplant->addVirologicalStatus($viroStatus);
                    }
                }
            } elseif (isset($existingByCode[$markerCode])) {
                // Value cleared — remove the record
                $transplant->removeVirologicalStatus($existingByCode[$markerCode]);
                unset($existingByCode[$markerCode]);
            }
        }
    }

    private function prefillHlaFields($form, Transplant $transplant): void
    {
        $hlaCodes = ['A', 'B', 'Cw', 'DR', 'DQ', 'DP'];
        foreach ($hlaCodes as $code) {
            $value = $transplant->getHlaIncompatibilityByCode($code);
            if ($value !== null) {
                $form->get('hla' . $code)->setData($value);
            }
        }
    }

    private function prefillVirologicalFields($form, Transplant $transplant): void
    {
        $virologicalMap = [
            'cmvStatus' => 'CMV',
            'ebvStatus' => 'EBV',
            'toxoplasmosisStatus' => 'toxoplasmosis',
        ];
        foreach ($virologicalMap as $fieldName => $markerCode) {
            $value = $transplant->getVirologicalStatusByCode($markerCode);
            if ($value !== null) {
                $form->get($fieldName)->setData($value);
            }
        }
    }
}
