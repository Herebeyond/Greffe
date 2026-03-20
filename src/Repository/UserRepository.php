<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a user by email or CRISTAL ID.
     */
    public function findByEmailOrCristalId(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.cristalId = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find active tech admin emails for the "forgot password" contact list.
     *
     * @return array<array{name: string, email: string}>
     */
    public function findTechAdminEmails(): array
    {
        $users = $this->createQueryBuilder('u')
            ->andWhere('u.isActive = true')
            ->orderBy('u.surname', 'ASC')
            ->getQuery()
            ->getResult();

        $admins = [];
        foreach ($users as $user) {
            if (in_array('ROLE_TECH_ADMIN', $user->getRoles(), true)) {
                $admins[] = [
                    'name' => $user->getName() . ' ' . $user->getSurname(),
                    'email' => $user->getEmail(),
                ];
            }
        }

        return $admins;
    }
}
