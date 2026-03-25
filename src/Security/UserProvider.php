<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Custom user provider that allows login with email or CRISTAL ID.
 * Account status checks (disabled) are handled by UserChecker.
 *
 * @implements UserProviderInterface<User>
 */
class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmailOrCristalId($identifier);

        if (!$user) {
            $exception = new UserNotFoundException(sprintf('Utilisateur "%s" introuvable.', $identifier));
            $exception->setUserIdentifier($identifier);
            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $refreshedUser = $this->userRepository->find($user->getId());

        if (!$refreshedUser) {
            $exception = new UserNotFoundException('User not found.');
            $exception->setUserIdentifier($user->getUserIdentifier());
            throw $exception;
        }

        // Check if account was disabled during active session (no info leak: user already authenticated)
        if (!$refreshedUser->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé.'
            );
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
