<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Form\PatientType;
use App\Form\PatientSearchType;
use App\Repository\PatientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients')]
#[IsGranted('ROLE_USER')]
class PatientController extends AbstractController
{
    public function __construct(
        private PatientRepository $patientRepository,
    ) {
    }

    /**
     * List and search patients.
     */
    #[Route('', name: 'app_patient_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $isPost = $request->isMethod('POST');
        $page = max(1, $isPost ? $request->request->getInt('page', 1) : $request->query->getInt('page', 1));
        $limit = 50;

        // Get search criteria from POST body (search form) or GET (redirect/initial load)
        $bag = $isPost ? $request->request : $request->query;
        $criteria = [
            'lastName' => $bag->get('lastName', ''),
            'firstName' => $bag->get('firstName', ''),
            'fileNumber' => $bag->get('fileNumber', ''),
            'city' => $bag->get('city', ''),
            'bloodGroup' => $bag->get('bloodGroup', ''),
            'rhesus' => $bag->get('rhesus', ''),
        ];

        // Validate CSRF token on POST requests
        if ($isPost && !$this->isCsrfTokenValid('patient_search', $bag->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer');

            return $this->redirectToRoute('app_patient_index');
        }

        // Check if at least one criteria is provided for search
        $hasSearchCriteria = !empty($criteria['lastName']) 
            || !empty($criteria['firstName']) 
            || !empty($criteria['fileNumber']) 
            || !empty($criteria['city'])
            || !empty($criteria['bloodGroup'])
            || !empty($criteria['rhesus']);

        $patients = [];
        $total = 0;
        $showConfirmation = false;
        $confirmed = $bag->getBoolean('confirmed', false);

        if ($hasSearchCriteria) {
            // First, check total count
            if (!$confirmed) {
                $countResult = $this->patientRepository->searchPaginated($criteria, 1, 1);
                $total = $countResult['total'];

                // If more than 200 results, ask for confirmation
                if ($total > 200) {
                    $showConfirmation = true;
                }
            }

            // If confirmed or less than 200 results, fetch data
            if ($confirmed || !$showConfirmation) {
                $result = $this->patientRepository->searchPaginated($criteria, $page, $limit);
                $patients = $result['patients'];
                $total = $result['total'];
            }
        }

        $totalPages = (int) ceil($total / $limit);

        return $this->render('patient/index.html.twig', [
            'patients' => $patients,
            'criteria' => $criteria,
            'hasSearchCriteria' => $hasSearchCriteria,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'showConfirmation' => $showConfirmation,
        ]);
    }

    /**
     * Create a new patient.
     */
    #[Route('/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->patientRepository->save($patient);

            $this->addFlash('success', 'Patient créé avec succès');

            return $this->redirectToRoute('app_patient_show', ['id' => $patient->getId()]);
        }

        return $this->render('patient/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    /**
     * View a patient's details.
     */
    #[Route('/{id}', name: 'app_patient_show', requirements: ['id' => '\d+'])]
    public function show(Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient, 'Vous n\'êtes pas autorisé à accéder à ce dossier patient');

        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    /**
     * Edit a patient.
     */
    #[Route('/{id}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient, 'Vous n\'êtes pas autorisé à accéder à ce dossier patient');
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $patient->setUpdatedAt(new \DateTimeImmutable());
            $this->patientRepository->save($patient);

            $this->addFlash('success', 'Patient modifié avec succès');

            return $this->redirectToRoute('app_patient_show', ['id' => $patient->getId()]);
        }

        return $this->render('patient/edit.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    /**
     * Delete a patient.
     */
    #[Route('/{id}/delete', name: 'app_patient_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, Patient $patient): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient, 'Vous n\'êtes pas autorisé à accéder à ce dossier patient');
        if ($this->isCsrfTokenValid('delete' . $patient->getId(), $request->request->get('_token'))) {
            $this->patientRepository->remove($patient);

            $this->addFlash('success', 'Patient supprimé avec succès');
        }

        return $this->redirectToRoute('app_patient_index');
    }
}
