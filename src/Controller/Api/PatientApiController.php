<?php

namespace App\Controller\Api;

use App\Entity\Patient;
use App\Repository\PatientAccessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class PatientApiController extends AbstractController
{
    public function __construct(
        private PatientAccessRepository $patientAccessRepository,
    ) {
    }

    /**
     * Returns the list of doctors (ROLE_DOCTOR) who have access to the given patient.
     * Used by nurses to select the practitioner when creating a consultation.
     */
    #[Route('/patients/{id}/doctors', name: 'api_patient_doctors', methods: ['GET'])]
    #[IsGranted('ROLE_NURSE')]
    public function getPatientDoctors(Patient $patient): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $accesses = $this->patientAccessRepository->findBy(['patient' => $patient]);

        $doctors = [];
        foreach ($accesses as $access) {
            $user = $access->getUser();
            if ($user === null) {
                continue;
            }
            if (!in_array('ROLE_DOCTOR', $user->getRoles(), true)) {
                continue;
            }
            $doctors[] = [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
            ];
        }

        return new JsonResponse($doctors);
    }
}
