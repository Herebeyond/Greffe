<?php

namespace App\Controller;

use App\Entity\Donor;
use App\Entity\DonorHlaTyping;
use App\Entity\DonorSerology;
use App\Entity\Transplant;
use App\Entity\User;
use App\Form\DonorType;
use App\Repository\DonorRepository;
use App\Repository\PatientRepository;
use App\Repository\TransplantRepository;
use App\Repository\Reference\HlaLocusRepository;
use App\Repository\Reference\SerologyMarkerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/donors')]
#[IsGranted('ROLE_USER')]
class DonorController extends AbstractController
{
    public function __construct(
        private DonorRepository $donorRepository,
        private TransplantRepository $transplantRepository,
        private PatientRepository $patientRepository,
        private HlaLocusRepository $hlaLocusRepository,
        private SerologyMarkerRepository $serologyMarkerRepository,
    ) {
    }

    #[Route('', name: 'app_donor_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isCoordinator = $this->isGranted('ROLE_TRANSPLANT_COORDINATOR');
        $isMedicalStaff = !$isCoordinator && ($this->isGranted('ROLE_DOCTOR') || $this->isGranted('ROLE_NURSE'));
        $donors = [];
        $searched = false;

        $bloodTypes = [];

        // Coordinator toggle: show only available donors by default
        $showAll = $isCoordinator && $request->query->get('showAll') === '1';

        if ($request->isMethod('POST')) {
            $searched = true;
            $cristalNumber = $request->request->get('cristalNumber');
            $donorType = $request->request->get('donorType');
            $bloodTypes = $request->request->all('bloodTypes');
            $allowedBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            $bloodTypes = array_intersect($bloodTypes, $allowedBloodTypes);
            $showAll = $isCoordinator && $request->request->get('showAll') === '1';

            if ($isMedicalStaff) {
                $donors = $this->donorRepository->searchByPractitioner(
                    $user,
                    $cristalNumber ?: null,
                    $bloodTypes,
                    $donorType ?: null,
                );
            } elseif ($isCoordinator && !$showAll) {
                $donors = $this->donorRepository->searchAvailable(
                    $cristalNumber ?: null,
                    $bloodTypes,
                    $donorType ?: null,
                );
            } else {
                $donors = $this->donorRepository->search(
                    $cristalNumber ?: null,
                    $bloodTypes,
                    $donorType ?: null,
                );
            }
        } else {
            if ($isMedicalStaff) {
                $donors = $this->donorRepository->findByPractitioner($user);
            } elseif ($isCoordinator && !$showAll) {
                $donors = $this->donorRepository->findAvailableOrderedByDate();
            } else {
                $donors = $this->donorRepository->findAllOrderedByDate();
            }
        }

        return $this->render('donor/index.html.twig', [
            'donors' => $donors,
            'searched' => $searched,
            'bloodTypes' => $bloodTypes,
            'showAll' => $showAll,
        ]);
    }

    #[Route('/{id}', name: 'app_donor_show', requirements: ['id' => '\d+'])]
    public function show(Donor $donor): Response
    {
        // Doctors/nurses (non-coordinators) can only view donors linked to their patients
        if (!$this->isGranted('ROLE_TRANSPLANT_COORDINATOR')
            && ($this->isGranted('ROLE_DOCTOR') || $this->isGranted('ROLE_NURSE'))) {
            /** @var User $user */
            $user = $this->getUser();
            $allowedDonors = $this->donorRepository->findByPractitioner($user);
            if (!in_array($donor, $allowedDonors, true)) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce donneur');
            }
        }

        // Build list of patient IDs the current user can access (for filtering recipient info)
        $accessiblePatientIds = [];
        foreach ($donor->getTransplants() as $transplant) {
            $patient = $transplant->getPatient();
            if ($patient !== null && $this->isGranted('VIEW_PATIENT', $patient)) {
                $accessiblePatientIds[] = $patient->getId();
            }
        }

        return $this->render('donor/show.html.twig', [
            'donor' => $donor,
            'accessiblePatientIds' => $accessiblePatientIds,
        ]);
    }

    #[Route('/new', name: 'app_donor_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function new(Request $request): Response
    {
        $donor = new Donor();

        $form = $this->createForm(DonorType::class, $donor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleHlaTypings($form, $donor);
            $this->handleSerologyResults($form, $donor);
            $this->donorRepository->save($donor);
            $this->addFlash('success', 'Donneur créé avec succès');

            return $this->redirectToRoute('app_donor_show', ['id' => $donor->getId()]);
        }

        return $this->render('donor/new.html.twig', [
            'form' => $form,
            'donor' => $donor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_donor_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function edit(Request $request, Donor $donor): Response
    {
        $form = $this->createForm(DonorType::class, $donor);

        // Pre-fill unmapped HLA fields from junction entities
        $this->prefillHlaFields($form, $donor);
        $this->prefillSerologyFields($form, $donor);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleHlaTypings($form, $donor);
            $this->handleSerologyResults($form, $donor);
            $donor->setUpdatedAt(new \DateTimeImmutable());
            $this->donorRepository->save($donor);
            $this->addFlash('success', 'Donneur modifié avec succès');

            return $this->redirectToRoute('app_donor_show', ['id' => $donor->getId()]);
        }

        return $this->render('donor/edit.html.twig', [
            'form' => $form,
            'donor' => $donor,
        ]);
    }

    #[Route('/{id}/assign', name: 'app_donor_assign', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function assign(Request $request, Donor $donor): Response
    {
        $isPost = $request->isMethod('POST');

        if ($request->isMethod('POST') && $request->request->has('transplantId')) {
            $transplantId = $request->request->getInt('transplantId');
            $transplant = $this->transplantRepository->find($transplantId);

            if ($transplant
                && $transplant->getDonor() === null
                && $this->isCsrfTokenValid('assign' . $donor->getId(), $request->request->get('_token'))
            ) {
                $transplant->setDonor($donor);
                $transplant->setUpdatedAt(new \DateTimeImmutable());
                $this->transplantRepository->save($transplant);
                $this->addFlash('success', 'Donneur assigné à la greffe avec succès');

                return $this->redirectToRoute('app_donor_show', ['id' => $donor->getId()]);
            }

            $this->addFlash('error', 'Impossible d\'assigner le donneur à cette greffe');
        }

        if ($request->isMethod('POST') && $request->request->has('patientId')) {
            $patientId = $request->request->getInt('patientId');
            $patient = $this->patientRepository->find($patientId);

            if ($patient
                && !$donor->isFullyAssigned()
                && $this->isCsrfTokenValid('assign' . $donor->getId(), $request->request->get('_token'))
            ) {
                $transplant = new Transplant();
                $this->applyAssignmentDraftDefaults($transplant, $patient, $donor);
                $this->transplantRepository->save($transplant);
                $this->addFlash('success', 'Donneur assigné au patient avec création de la pré-affectation de greffe');

                return $this->redirectToRoute('app_donor_show', ['id' => $donor->getId()]);
            }

            $this->addFlash('error', 'Impossible de créer la pré-affectation de greffe pour ce patient');
        }

        $fileNumber = null;
        if ($request->isMethod('POST') && $request->request->has('fileNumber')) {
            $fileNumber = trim($request->request->get('fileNumber', ''));
        }

        $transplantPage = max(1, $isPost ? $request->request->getInt('transplantPage', 1) : $request->query->getInt('transplantPage', 1));
        $patientPage = max(1, $isPost ? $request->request->getInt('patientPage', 1) : $request->query->getInt('patientPage', 1));
        $pageSize = 25;

        $allTransplants = $this->transplantRepository->findWithoutDonor($fileNumber ?: null);
        $allPatientsWithoutTransplant = $this->patientRepository->findWithoutTransplantRecord($fileNumber ?: null);

        $transplantTotal = count($allTransplants);
        $transplantTotalPages = max(1, (int) ceil($transplantTotal / $pageSize));
        $transplantPage = min($transplantPage, $transplantTotalPages);
        $transplants = array_slice($allTransplants, ($transplantPage - 1) * $pageSize, $pageSize);

        $patientTotal = count($allPatientsWithoutTransplant);
        $patientTotalPages = max(1, (int) ceil($patientTotal / $pageSize));
        $patientPage = min($patientPage, $patientTotalPages);
        $patientsWithoutTransplant = array_slice($allPatientsWithoutTransplant, ($patientPage - 1) * $pageSize, $pageSize);

        return $this->render('donor/assign.html.twig', [
            'donor' => $donor,
            'transplants' => $transplants,
            'patientsWithoutTransplant' => $patientsWithoutTransplant,
            'fileNumber' => $fileNumber,
            'transplantTotal' => $transplantTotal,
            'transplantPage' => $transplantPage,
            'transplantTotalPages' => $transplantTotalPages,
            'patientTotal' => $patientTotal,
            'patientPage' => $patientPage,
            'patientTotalPages' => $patientTotalPages,
        ]);
    }

    private function applyAssignmentDraftDefaults(Transplant $transplant, $patient, Donor $donor): void
    {
        $transplant->setPatient($patient);
        $transplant->setRank($this->transplantRepository->getNextRankForPatient($patient));
        $transplant->setDonor($donor);
        $transplant->setDonorType($donor->getDonorType());
        $transplant->setTransplantDate(null);
        $transplant->setIsGraftFunctional(false);
        $transplant->setGraftEndDate(null);
        $transplant->setGraftEndCause(null);
        $transplant->setSurgeonName($patient->getPrimaryAccess()?->getUser()?->getFullName());
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
    }

    #[Route('/{id}/unassign/{transplantId}', name: 'app_donor_unassign', methods: ['POST'], requirements: ['id' => '\d+', 'transplantId' => '\d+'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function unassign(Request $request, Donor $donor, int $transplantId): Response
    {
        $transplant = $this->transplantRepository->find($transplantId);

        if ($transplant
            && $transplant->getDonor() === $donor
            && $this->isCsrfTokenValid('unassign' . $donor->getId() . '-' . $transplantId, $request->request->get('_token'))
        ) {
            $patient = $transplant->getPatient();
            $transplant->setDonor(null);
            $transplant->setUpdatedAt(new \DateTimeImmutable());
            $this->transplantRepository->save($transplant);
            $this->addFlash('success', 'Donneur retiré de la greffe avec succès');

            if ($request->request->get('redirectTo') === 'transplant' && $patient) {
                return $this->redirectToRoute('app_transplant_show', [
                    'patientId' => $patient->getId(),
                    'id' => $transplantId,
                ]);
            }
        }

        return $this->redirectToRoute('app_donor_show', ['id' => $donor->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_donor_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRANSPLANT_COORDINATOR')]
    public function delete(Request $request, Donor $donor): Response
    {
        if ($this->isCsrfTokenValid('delete' . $donor->getId(), $request->request->get('_token'))) {
            $this->donorRepository->remove($donor);
            $this->addFlash('success', 'Donneur supprimé avec succès');
        }

        return $this->redirectToRoute('app_donor_index');
    }

    private function handleHlaTypings($form, Donor $donor): void
    {
        $hlaCodes = ['A', 'B', 'Cw', 'DR', 'DQ', 'DP'];

        // Remove existing typings
        foreach ($donor->getHlaTypings()->toArray() as $typing) {
            $donor->removeHlaTyping($typing);
        }

        foreach ($hlaCodes as $code) {
            $fieldName = 'hla' . $code;
            $value = $form->get($fieldName)->getData();
            if ($value !== null) {
                $locus = $this->hlaLocusRepository->findOneByCode($code);
                if ($locus) {
                    $typing = new DonorHlaTyping();
                    $typing->setDonor($donor);
                    $typing->setHlaLocus($locus);
                    $typing->setValue((int) $value);
                    $donor->addHlaTyping($typing);
                }
            }
        }
    }

    private function handleSerologyResults($form, Donor $donor): void
    {
        $serologyCodes = ['cmv', 'ebv', 'hiv', 'htlv', 'syphilis', 'hcv', 'agHbs', 'acHbs', 'acHbc', 'toxoplasmosis', 'arnc', 'dnaB'];

        // Remove existing results
        foreach ($donor->getSerologyResults()->toArray() as $result) {
            $donor->removeSerologyResult($result);
        }

        foreach ($serologyCodes as $code) {
            $value = $form->get($code)->getData();
            if ($value !== null) {
                $marker = $this->serologyMarkerRepository->findOneByCode($code);
                if ($marker) {
                    $serology = new DonorSerology();
                    $serology->setDonor($donor);
                    $serology->setSerologyMarker($marker);
                    $serology->setResult($value);
                    $donor->addSerologyResult($serology);
                }
            }
        }
    }

    private function prefillHlaFields($form, Donor $donor): void
    {
        $hlaCodes = ['A', 'B', 'Cw', 'DR', 'DQ', 'DP'];
        foreach ($hlaCodes as $code) {
            $value = $donor->getHlaValueByCode($code);
            if ($value !== null) {
                $form->get('hla' . $code)->setData($value);
            }
        }
    }

    private function prefillSerologyFields($form, Donor $donor): void
    {
        $serologyCodes = ['cmv', 'ebv', 'hiv', 'htlv', 'syphilis', 'hcv', 'agHbs', 'acHbs', 'acHbc', 'toxoplasmosis', 'arnc', 'dnaB'];
        foreach ($serologyCodes as $code) {
            $value = $donor->getSerologyResultByCode($code);
            if ($value !== null) {
                $form->get($code)->setData($value);
            }
        }
    }
}
