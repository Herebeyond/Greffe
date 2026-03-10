<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter that controls write and delete operations on medical records.
 * 
 * Usage in controllers:
 *   #[IsGranted('CAN_WRITE')]
 *   or
 *   $this->denyAccessUnlessGranted('CAN_WRITE');
 */
class WriteAccessVoter extends Voter
{
    public const CAN_WRITE = 'CAN_WRITE';
    public const CAN_DELETE = 'CAN_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CAN_WRITE, self::CAN_DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $roles = $user->getRoles();

        // Medical admins can write and delete
        if (in_array('ROLE_MEDICAL_ADMIN', $roles, true)) {
            return true;
        }

        // For CAN_DELETE, only medical admins are allowed (already handled above)
        if ($attribute === self::CAN_DELETE) {
            return false;
        }

        // For CAN_WRITE, doctors can write
        if (in_array('ROLE_DOCTOR', $roles, true)) {
            return true;
        }

        // Nurses have read-only access - cannot write or delete
        if (in_array('ROLE_NURSE', $roles, true)) {
            return false;
        }

        // Default deny for other roles (ROLE_USER, ROLE_PATIENT, ROLE_TECH_ADMIN)
        return false;
    }
}
