<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return ['unread_notification_count' => 0];
        }

        return [
            'unread_notification_count' => $this->notificationRepository->countUnread($user),
        ];
    }
}
