<?php

namespace App\Security\Voter;

use App\Entity\Patient;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Controls access to individual patient files.
 *
 * Access model (hybrid, Art. L1110-4 & L1110-12 CSP):
 *  - CHU practitioner (isChuPractitioner) → granted (same care team)
 *  - External practitioner assigned to the patient → granted
 *  - Otherwise → denied
 *
 * ⚠️ LEGAL NOTE (Art. L1110-4 CSP, RGPD Art. 25/32):
 * Technical administrators (ROLE_TECH_ADMIN) have NO access to patient data.
 * Only healthcare professionals in the care team may view patient files.
 * ROLE_MEDICAL_ADMIN grants admin privileges but patient access relies on
 * normal rules (isChuPractitioner or explicit patient assignment).
 * A future "bris de glace" (break-the-glass) mechanism could allow audited
 * emergency access — see docs/PATIENT_ACCESS_LEGAL.md §5.
 */
class PatientAccessVoter extends Voter
{
    public const VIEW_PATIENT = 'VIEW_PATIENT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW_PATIENT && $subject instanceof Patient;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Patient $patient */
        $patient = $subject;

        // CHU transplant service practitioners can access all patients
        if ($user->isChuPractitioner()) {
            return true;
        }

        // External practitioners can only access their assigned patients
        if ($patient->isAuthorizedPractitioner($user)) {
            return true;
        }

        return false;
    }
}
