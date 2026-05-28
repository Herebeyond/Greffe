<?php

namespace App\Controller;

use App\Entity\Transplant;
use App\Entity\TransplantHlaIncompatibility;
use App\Entity\TransplantVirologicalStatus;
use App\Entity\Patient;
use App\Form\TransplantType;
use App\Form\DonorDataType;
use App\Repository\DonorRepository;
use App\Repository\TransplantRepository;
use App\Repository\Reference\HlaLocusRepository;
use App\Repository\Reference\VirologicalMarkerRepository;
use App\Service\FileUploadService;
use App\Service\NotificationService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/transplants', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class TransplantController extends AbstractController
{
    private const UPLOAD_DIR = 'transplants';

    public function __construct(
        private TransplantRepository $transplantRepository,
        private DonorRepository $donorRepository,
        private HlaLocusRepository $hlaLocusRepository,
        private VirologicalMarkerRepository $virologicalMarkerRepository,
        private FileUploadService $fileUploadService,
        private NotificationService $notificationService,
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

    #[Route('/{id}/assign-donor', name: 'app_transplant_assign_donor', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function assignDonor(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($transplant->getDonor() !== null) {
            $this->addFlash('error', 'Cette greffe a déjà un donneur assigné');
            return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
        }

        if ($request->isMethod('POST') && $request->request->has('donorId')) {
            $donorId = $request->request->getInt('donorId');
            $donor = $this->donorRepository->find($donorId);

            if ($donor
                && !$donor->isFullyAssigned()
                && $this->isCsrfTokenValid('assign_donor' . $transplant->getId(), $request->request->get('_token'))
            ) {
                /** @var \App\Entity\User $currentUser */
                $currentUser = $this->getUser();
                $transplant->setDonor($donor);
                $transplant->setUpdatedAt(new \DateTimeImmutable());
                $this->transplantRepository->save($transplant);

                $primaryDoctor = $patient->getPrimaryAccess()?->getUser();
                if ($primaryDoctor !== null) {
                    $this->notificationService->notifyDonorLinked($primaryDoctor, $currentUser, $patient, $donor);
                }

                $this->addFlash('success', 'Donneur assigné à la greffe avec succès');

                return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
            }

            $this->addFlash('error', 'Impossible d\'assigner ce donneur à la greffe');
        }

        $donors = $this->donorRepository->findAvailableOrderedByDate();

        return $this->render('transplant/assign_donor.html.twig', [
            'patient' => $patient,
            'transplant' => $transplant,
            'donors' => $donors,
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
    public function new(Request $request, #[MapEntity(id: 'patientId')] Patient $patient): Response
    {
        if (!$this->isGranted('CAN_WRITE') && !$this->isGranted('ROLE_TRANSPLANT_COORDINATOR')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $transplant = new Transplant();
        $transplant->setPatient($patient);

        $preselectedDonor = null;
        $preselectedDonorId = $request->query->getInt('donorId', 0);
        if ($preselectedDonorId > 0) {
            $candidateDonor = $this->donorRepository->find($preselectedDonorId);
            if ($candidateDonor !== null && !$candidateDonor->isFullyAssigned()) {
                $preselectedDonor = $candidateDonor;
            }
        }

        $isAssignmentDraft = $this->isGranted('ROLE_TRANSPLANT_COORDINATOR') && $preselectedDonor !== null;
        if ($isAssignmentDraft) {
            $this->applyAssignmentDraftDefaults($transplant, $patient, $preselectedDonor);
        }

        $form = $this->createForm(TransplantType::class, $transplant, [
            'is_assignment_draft' => $isAssignmentDraft,
            'validation_groups' => $isAssignmentDraft ? false : null,
        ]);
        $form->handleRequest($request);

        // Determine donor type for the donor sub-form
        $donorTypeCode = $isAssignmentDraft
            ? $transplant->getDonorType()?->getCode()
            : ($request->request->all('transplant')['donorType'] ?? $transplant->getDonorType()?->getCode());
        $donorForm = null;

        if (!$isAssignmentDraft && $donorTypeCode) {
            $donorForm = $this->createForm(DonorDataType::class, $transplant->getDonorData(), [
                'donor_type' => $donorTypeCode,
            ]);
            $donorForm->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isAssignmentDraft) {
                $this->applyAssignmentDraftDefaults($transplant, $patient, $preselectedDonor);
            }
            if ($preselectedDonor !== null && $transplant->getDonor() === null) {
                $transplant->setDonor($preselectedDonor);
            }
            if ($donorForm && $donorForm->isSubmitted() && $donorForm->isValid()) {
                $transplant->setDonorData($donorForm->getData());
            }
            $this->handleHlaIncompatibilities($form, $transplant);
            $this->handleVirologicalStatuses($form, $transplant);
            $this->handleFileUploads($form, $transplant);
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Greffe ajoutée avec succès');

            return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
        }

        return $this->render('transplant/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
            'donorForm' => $donorForm,
            'donorType' => $donorTypeCode,
            'preselectedDonor' => $preselectedDonor,
            'isAssignmentDraft' => $isAssignmentDraft,
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
            $this->handleFileUploads($form, $transplant);
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

    private function applyAssignmentDraftDefaults(Transplant $transplant, Patient $patient, ?\App\Entity\Donor $preselectedDonor): void
    {
        $transplant->setPatient($patient);
        $transplant->setRank($this->transplantRepository->getNextRankForPatient($patient));
        $transplant->setDonor($preselectedDonor);
        $transplant->setDonorType($preselectedDonor?->getDonorType());
        $transplant->setTransplantDate(null);
        $transplant->setIsGraftFunctional(false);
        $transplant->setGraftEndDate(null);
        $transplant->setGraftEndCause(null);
        $transplant->setDeclampingDate(null);
        $transplant->setDeclampingTime(null);
        $transplant->setHarvestSide(null);
        $transplant->setTransplantSide(null);
        $transplant->setPeritonealPosition(null);
        $transplant->setTotalIschemiaMinutes(null);
        $transplant->setAnastomosisDuration(null);
        $transplant->setJjProbe(false);
        $transplant->setImmunologicalRisk(null);
        $transplant->getImmunosuppressiveDrugs()->clear();
        $transplant->setDialysis(false);
        $transplant->setLastDialysisDate(null);
        $transplant->setHasProtocol(false);
        $transplant->setComment(null);
        $transplant->setDonorData(null);

        $primaryDoctor = $patient->getPrimaryAccess()?->getUser();
        $transplant->setSurgeonName($primaryDoctor?->getFullName());
    }

    #[Route('/{id}/delete', name: 'app_transplant_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete' . $transplant->getId(), $request->request->get('_token'))) {
            // Clean up uploaded files
            $this->fileUploadService->deleteMultiple($transplant->getOperativeReportFilenames(), self::UPLOAD_DIR);
            $this->fileUploadService->deleteMultiple($transplant->getProtocolFilenames(), self::UPLOAD_DIR);
            $this->transplantRepository->remove($transplant);
            $this->addFlash('success', 'Greffe supprimée avec succès');
        }

        return $this->redirectToRoute('app_transplant_index', ['patientId' => $patient->getId()]);
    }

    #[Route('/{id}/download/{type}/{filename}', name: 'app_transplant_download', requirements: ['id' => '\d+', 'type' => 'operative-report|protocol'])]
    public function download(#[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant, string $type, string $filename): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $filenames = match ($type) {
            'operative-report' => $transplant->getOperativeReportFilenames(),
            'protocol' => $transplant->getProtocolFilenames(),
        };

        if (!in_array($filename, $filenames, true)) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $filePath = $this->fileUploadService->getFilePath($filename, self::UPLOAD_DIR);

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé sur le serveur.');
        }

        return $this->file($filePath, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/{id}/delete-file/{type}/{filename}', name: 'app_transplant_delete_file', methods: ['POST'], requirements: ['id' => '\d+', 'type' => 'operative-report|protocol'])]
    #[IsGranted('CAN_WRITE')]
    public function deleteFile(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Transplant $transplant, string $type, string $filename): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete-file' . $transplant->getId(), $request->request->get('_token'))) {
            match ($type) {
                'operative-report' => (function () use ($transplant, $filename) {
                    if (in_array($filename, $transplant->getOperativeReportFilenames(), true)) {
                        $this->fileUploadService->delete($filename, self::UPLOAD_DIR);
                        $transplant->removeOperativeReportFilename($filename);
                    }
                })(),
                'protocol' => (function () use ($transplant, $filename) {
                    if (in_array($filename, $transplant->getProtocolFilenames(), true)) {
                        $this->fileUploadService->delete($filename, self::UPLOAD_DIR);
                        $transplant->removeProtocolFilename($filename);
                    }
                })(),
            };
            $transplant->setUpdatedAt(new \DateTimeImmutable());
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Fichier supprimé avec succès');
        }

        return $this->redirectToRoute('app_transplant_show', ['patientId' => $patient->getId(), 'id' => $transplant->getId()]);
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

    private function handleFileUploads($form, Transplant $transplant): void
    {
        $operativeReportFiles = $form->get('operativeReportFiles')->getData();
        if ($operativeReportFiles) {
            $newFilenames = $this->fileUploadService->uploadMultiple($operativeReportFiles, self::UPLOAD_DIR);
            foreach ($newFilenames as $f) {
                $transplant->addOperativeReportFilename($f);
            }
        }

        $protocolFiles = $form->get('protocolFiles')->getData();
        if ($protocolFiles) {
            $newFilenames = $this->fileUploadService->uploadMultiple($protocolFiles, self::UPLOAD_DIR);
            foreach ($newFilenames as $f) {
                $transplant->addProtocolFilename($f);
            }
        }
    }
}
