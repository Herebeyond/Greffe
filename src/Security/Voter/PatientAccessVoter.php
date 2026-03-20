<?php

namespace App\Security\Voter;

use App\Entity\Patient;
use App\Entity\User;
use App\Repository\BreakTheGlassAccessRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Controls access to individual patient files.
 *
 * Access model (Art. L1110-4 & L1110-12 CSP):
 *  - Practitioner assigned to the patient → granted
 *  - Active break-the-glass access for this user+patient → granted
 *  - Otherwise → denied
 *
 * ⚠️ LEGAL NOTE (Art. L1110-4 CSP, RGPD Art. 25/32):
 * Technical administrators (ROLE_TECH_ADMIN) have NO access to patient data.
 * Only healthcare professionals in the care team may view patient files.
 * "Bris de glace" (break-the-glass) allows audited emergency access
 * — see docs/PATIENT_ACCESS_LEGAL.md §5.
 */
class PatientAccessVoter extends Voter
{
    public const VIEW_PATIENT = 'VIEW_PATIENT';

    public function __construct(
        private BreakTheGlassAccessRepository $btgRepository,
    ) {
    }

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

        // Practitioners can access their assigned patients
        if ($patient->isAuthorizedPractitioner($user)) {
            return true;
        }

        // Check for active break-the-glass emergency access
        if ($this->btgRepository->hasActiveAccess($user, $patient)) {
            return true;
        }

        return false;
    }
}
