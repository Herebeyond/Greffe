<?php

namespace App\Controller;

use App\Entity\BiologicalResult;
use App\Entity\Patient;
use App\Form\BiologicalResultType;
use App\Repository\BiologicalResultRepository;
use App\Service\FileUploadService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patients/{patientId}/resultats-biologiques', requirements: ['patientId' => '\d+'])]
#[IsGranted('ROLE_USER')]
class BiologicalResultController extends AbstractController
{
    private const UPLOAD_DIR = 'biological_results';

    public function __construct(
        private BiologicalResultRepository $biologicalResultRepository,
        private FileUploadService $fileUploadService,
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
            $reportFiles = $form->get('reportFiles')->getData();
            if ($reportFiles) {
                $newFilenames = $this->fileUploadService->uploadMultiple($reportFiles, self::UPLOAD_DIR);
                foreach ($newFilenames as $f) {
                    $result->addReportFilename($f);
                }
            }
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
            $reportFiles = $form->get('reportFiles')->getData();
            if ($reportFiles) {
                $newFilenames = $this->fileUploadService->uploadMultiple($reportFiles, self::UPLOAD_DIR);
                foreach ($newFilenames as $f) {
                    $result->addReportFilename($f);
                }
            }
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
            $this->fileUploadService->deleteMultiple($result->getReportFilenames(), self::UPLOAD_DIR);
            $this->biologicalResultRepository->remove($result);
            $this->addFlash('success', 'Résultat biologique supprimé avec succès');
        }

        return $this->redirectToRoute('app_biological_result_index', ['patientId' => $patient->getId()]);
    }

    #[Route('/{id}/download/{filename}', name: 'app_biological_result_download', requirements: ['id' => '\d+'])]
    public function download(#[MapEntity(id: 'patientId')] Patient $patient, BiologicalResult $result, string $filename): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if (!in_array($filename, $result->getReportFilenames(), true)) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $filePath = $this->fileUploadService->getFilePath($filename, self::UPLOAD_DIR);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé sur le serveur.');
        }

        return $this->file($filePath, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/{id}/delete-file/{filename}', name: 'app_biological_result_delete_file', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_WRITE')]
    public function deleteFile(Request $request, #[MapEntity(id: 'patientId')] Patient $patient, BiologicalResult $result, string $filename): Response
    {
        $this->denyAccessUnlessGranted('VIEW_PATIENT', $patient);

        if ($this->isCsrfTokenValid('delete-file' . $result->getId(), $request->request->get('_token'))) {
            if (in_array($filename, $result->getReportFilenames(), true)) {
                $this->fileUploadService->delete($filename, self::UPLOAD_DIR);
                $result->removeReportFilename($filename);
                $this->biologicalResultRepository->save($result);
            }
            $this->addFlash('success', 'Fichier supprimé avec succès');
        }

        return $this->redirectToRoute('app_biological_result_edit', ['patientId' => $patient->getId(), 'id' => $result->getId()]);
    }
}
