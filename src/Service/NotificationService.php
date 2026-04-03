<?php

namespace App\Service;

use App\Entity\Donor;
use App\Entity\Notification;
use App\Entity\Patient;
use App\Entity\User;
use App\Repository\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
    ) {
    }

    public function notifyAccessGranted(User $recipient, User $grantedBy, Patient $patient, string $accessLevel): void
    {
        $levelLabel = $accessLevel === 'primary' ? 'principal' : 'secondaire';
        $message = sprintf(
            '%s vous a accordé un accès %s au patient %s.',
            $grantedBy->getFullName(),
            $levelLabel,
            $patient->getFullName()
        );

        $this->createNotification(
            $recipient,
            $grantedBy,
            Notification::TYPE_ACCESS_GRANTED,
            $message,
            $patient
        );
    }

    public function notifyAccessRevoked(User $recipient, User $revokedBy, Patient $patient): void
    {
        $message = sprintf(
            '%s a révoqué votre accès au patient %s.',
            $revokedBy->getFullName(),
            $patient->getFullName()
        );

        $this->createNotification(
            $recipient,
            $revokedBy,
            Notification::TYPE_ACCESS_REVOKED,
            $message,
            $patient
        );
    }

    public function notifyAccessTransferredTo(User $newPrimary, User $transferredBy, Patient $patient): void
    {
        $message = sprintf(
            '%s vous a transféré l\'accès principal au patient %s.',
            $transferredBy->getFullName(),
            $patient->getFullName()
        );

        $this->createNotification(
            $newPrimary,
            $transferredBy,
            Notification::TYPE_ACCESS_TRANSFERRED,
            $message,
            $patient
        );
    }

    public function notifyAccessTransferredFrom(User $oldPrimary, User $transferredBy, Patient $patient, User $newPrimary): void
    {
        $message = sprintf(
            '%s a transféré votre accès principal au patient %s vers %s. Vous n\'avez plus accès à ce patient.',
            $transferredBy->getFullName(),
            $patient->getFullName(),
            $newPrimary->getFullName()
        );

        $this->createNotification(
            $oldPrimary,
            $transferredBy,
            Notification::TYPE_ACCESS_TRANSFERRED,
            $message,
            $patient
        );
    }

    public function notifyDonorLinked(User $recipient, User $linkedBy, Patient $patient, Donor $donor): void
    {
        $message = sprintf(
            '%s a associé un donneur (CRISTAL: %s) au patient %s.',
            $linkedBy->getFullName(),
            $donor->getCristalNumber() ?? 'N/A',
            $patient->getFullName()
        );

        $this->createNotification(
            $recipient,
            $linkedBy,
            Notification::TYPE_DONOR_LINKED,
            $message,
            $patient,
            $donor
        );
    }

    private function createNotification(
        User $recipient,
        ?User $triggeredBy,
        string $type,
        string $message,
        ?Patient $patient = null,
        ?Donor $donor = null,
    ): void {
        // Don't notify yourself
        if ($triggeredBy !== null && $recipient->getId() === $triggeredBy->getId()) {
            return;
        }

        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setTriggeredBy($triggeredBy);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setRelatedPatient($patient);
        $notification->setRelatedDonor($donor);

        $this->notificationRepository->save($notification);
    }
}
