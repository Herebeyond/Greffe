<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user: Sam Gamegie
        $admin = new User();
        $admin->setName('Sam');
        $admin->setSurname('Gamegie');
        $admin->setEmail('admin@admin.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        // Doctor user: John Doe
        $doctor = new User();
        $doctor->setName('John');
        $doctor->setSurname('Doe');
        $doctor->setEmail('test@test.fr');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $doctor->setPassword($this->passwordHasher->hashPassword($doctor, 'password'));
        $manager->persist($doctor);

        $manager->flush();
    }
}
