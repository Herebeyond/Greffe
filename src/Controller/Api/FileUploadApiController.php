<?php

namespace App\Controller\Api;

use App\Entity\Consultation;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class FileUploadApiController extends AbstractController
{
    private const UPLOAD_DIR = 'consultations';

    public function __construct(
        private FileUploadService $fileUploadService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/consultations/{id}/upload', name: 'api_consultation_upload', methods: ['POST'])]
    #[IsGranted('ROLE_DOCTOR')]
    public function uploadConsultationAttachment(Consultation $consultation, Request $request): JsonResponse
    {
        $patient = $consultation->getPatient();
        if ($patient === null) {
            return new JsonResponse(['error' => 'Consultation sans patient'], Response::HTTP_BAD_REQUEST);
        }

        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        $file = $request->files->get('file');
        if ($file === null) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé'], Response::HTTP_BAD_REQUEST);
        }

        $filename = $this->fileUploadService->upload($file, self::UPLOAD_DIR);
        $consultation->addAttachmentFilename($filename);
        $this->entityManager->flush();

        return new JsonResponse([
            'filename' => $filename,
            'message' => 'Fichier uploadé avec succès',
        ], Response::HTTP_CREATED);
    }

    #[Route('/consultations/{id}/download/{filename}', name: 'api_consultation_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function downloadConsultationAttachment(Consultation $consultation, string $filename): BinaryFileResponse
    {
        $patient = $consultation->getPatient();
        if ($patient === null) {
            throw $this->createNotFoundException('Consultation sans patient');
        }

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
}
