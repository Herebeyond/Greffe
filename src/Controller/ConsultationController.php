<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Form\ConsultationType;
use App\Repository\ConsultationRepository;
use App\Service\FileUploadService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/consultations', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class ConsultationController extends AbstractController
{
    private const UPLOAD_DIR = 'consultations';

    public function __construct(
        private ConsultationRepository $consultationRepository,
        private FileUploadService $fileUploadService,
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
            $attachmentFiles = $form->get('attachmentFiles')->getData();
            if ($attachmentFiles) {
                $newFilenames = $this->fileUploadService->uploadMultiple($attachmentFiles, self::UPLOAD_DIR);
                foreach ($newFilenames as $f) {
                    $consultation->addAttachmentFilename($f);
                }
            }
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

        if ($consultation->getCreatedBy() !== null && $consultation->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le praticien ayant créé cette consultation peut la modifier.');
            return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
        }

        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attachmentFiles = $form->get('attachmentFiles')->getData();
            if ($attachmentFiles) {
                $newFilenames = $this->fileUploadService->uploadMultiple($attachmentFiles, self::UPLOAD_DIR);
                foreach ($newFilenames as $f) {
                    $consultation->addAttachmentFilename($f);
                }
            }
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

        if ($consultation->getCreatedBy() !== null && $consultation->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le praticien ayant créé cette consultation peut la supprimer.');
            return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $consultation->getId(), $request->request->get('_token'))) {
            $this->fileUploadService->deleteMultiple($consultation->getAttachmentFilenames(), self::UPLOAD_DIR);
            $this->consultationRepository->remove($consultation);
            $this->addFlash('success', 'Consultation supprimée avec succès');
        }

        return $this->redirectToRoute('app_consultation_index', ['patientId' => $patient->getId()]);
    }

    #[Route('/{id}/download/{filename}', name: 'app_consultation_download', requirements: ['id' => '\d+'])]
    public function download(#[MapEntity(id: 'patientId')] Patient $patient, Consultation $consultation, string $filename): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if (!in_array($filename, $consultation->getAttachmentFilenames(), true)) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $filePath = $this->fileUploadService->getFilePath($filename, self::UPLOAD_DIR);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé sur le serveur.');
        }

        return $this->file($filePath, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/{id}/delete-file/{filename}', name: 'app_consultation_delete_file', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function deleteFile(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, Consultation $consultation, string $filename): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete-file' . $consultation->getId(), $request->request->get('_token'))) {
            if (in_array($filename, $consultation->getAttachmentFilenames(), true)) {
                $this->fileUploadService->delete($filename, self::UPLOAD_DIR);
                $consultation->removeAttachmentFilename($filename);
                $this->consultationRepository->save($consultation);
            }
            $this->addFlash('success', 'Fichier supprimé avec succès');
        }

        return $this->redirectToRoute('app_consultation_edit', ['patientId' => $patient->getId(), 'id' => $consultation->getId()]);
    }
}
