<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * List all notifications for the current user.
     */
    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $this->notificationRepository->findByRecipient($user);
        $unreadCount = $this->notificationRepository->countUnread($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markAsRead(Request $request, Notification $notification): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($notification->getRecipient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('notification_read' . $notification->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');
            return $this->redirectToRoute('app_notification_index');
        }

        $notification->markAsRead();
        $this->notificationRepository->save($notification);

        return $this->redirectToRoute('app_notification_index');
    }

    /**
     * Mark all notifications as read.
     */
    #[Route('/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('notification_read_all', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide');
            return $this->redirectToRoute('app_notification_index');
        }

        $this->notificationRepository->markAllAsRead($user);

        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues');
        return $this->redirectToRoute('app_notification_index');
    }
}
