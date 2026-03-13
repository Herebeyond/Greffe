<?php

namespace App\Service;

use App\Entity\PasswordHistory;
use App\Entity\User;
use App\Repository\PasswordHistoryRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordHistoryService
{
    public function __construct(
        private PasswordHistoryRepository $passwordHistoryRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Record the old password hash in the history before changing to a new password.
     */
    public function recordPasswordChange(User $user, string $reason): void
    {
        $currentHash = $user->getPassword();

        if ($currentHash === null) {
            return;
        }

        $history = new PasswordHistory();
        $history->setUser($user);
        $history->setHashedPassword($currentHash);
        $history->setChangeReason($reason);

        $this->passwordHistoryRepository->save($history);
    }

    /**
     * Check if a plain-text password was already used by this user.
     * Checks current password + last N passwords in history.
     */
    public function isPasswordReused(User $user, string $plainPassword, int $historyDepth = 5): bool
    {
        // Check current password first
        if ($user->getPassword() !== null && $this->passwordHasher->isPasswordValid($user, $plainPassword)) {
            return true;
        }

        // Check against password history
        $previousHashes = $this->passwordHistoryRepository->findRecentHashesByUser($user, $historyDepth);

        foreach ($previousHashes as $hash) {
            // UserPasswordHasherInterface works with full User objects, so we need
            // to temporarily set the hash on a clone to check
            $tempUser = clone $user;
            $tempUser->setPassword($hash);

            if ($this->passwordHasher->isPasswordValid($tempUser, $plainPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the date of the last password change for a user.
     */
    public function getLastPasswordChangeDate(User $user): ?\DateTimeImmutable
    {
        return $this->passwordHistoryRepository->findLastChangeDate($user);
    }
}
