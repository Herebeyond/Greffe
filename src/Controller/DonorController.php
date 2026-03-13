<?php

namespace App\Controller;

use App\Entity\Donor;
use App\Form\DonorType;
use App\Repository\DonorRepository;
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
    ) {
    }

    #[Route('', name: 'app_donor_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $donors = [];
        $searched = false;

        if ($request->isMethod('POST')) {
            $searched = true;
            $cristalNumber = $request->request->get('cristalNumber');
            $bloodGroup = $request->request->get('bloodGroup');
            $donorType = $request->request->get('donorType');

            $donors = $this->donorRepository->search(
                $cristalNumber ?: null,
                $bloodGroup ?: null,
                $donorType ?: null,
            );
        } else {
            $donors = $this->donorRepository->findAllOrderedByDate();
        }

        return $this->render('donor/index.html.twig', [
            'donors' => $donors,
            'searched' => $searched,
        ]);
    }

    #[Route('/{id}', name: 'app_donor_show', requirements: ['id' => '\d+'])]
    public function show(Donor $donor): Response
    {
        return $this->render('donor/show.html.twig', [
            'donor' => $donor,
        ]);
    }

    #[Route('/new', name: 'app_donor_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_WRITE')]
    public function new(Request $request): Response
    {
        $donor = new Donor();

        // Pre-set donor type from query parameter if provided
        $donorType = $request->query->get('type');
        if ($donorType && in_array($donorType, [Donor::TYPE_LIVING, Donor::TYPE_DECEASED_ENCEPHALIC, Donor::TYPE_DECEASED_CARDIAC_ARREST])) {
            $donor->setDonorType($donorType);
        }

        $form = $this->createForm(DonorType::class, $donor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    #[IsGranted('CAN_WRITE')]
    public function edit(Request $request, Donor $donor): Response
    {
        $form = $this->createForm(DonorType::class, $donor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}/delete', name: 'app_donor_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('CAN_DELETE')]
    public function delete(Request $request, Donor $donor): Response
    {
        if ($this->isCsrfTokenValid('delete' . $donor->getId(), $request->request->get('_token'))) {
            $this->donorRepository->remove($donor);
            $this->addFlash('success', 'Donneur supprimé avec succès');
        }

        return $this->redirectToRoute('app_donor_index');
    }
}
