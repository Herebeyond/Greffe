<?php

namespace App\EventSubscriber;

use App\Entity\Consultation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, entity: Consultation::class)]
class ConsultationSubscriber
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function prePersist(Consultation $consultation): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($consultation->getCreatedBy() === null) {
            $consultation->setCreatedBy($user);
        }

        if ($consultation->getPractitionerName() === null || $consultation->getPractitionerName() === '') {
            $consultation->setPractitionerName($user->getFullName());
        }
    }
}
