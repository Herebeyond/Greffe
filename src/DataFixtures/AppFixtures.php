<?php

namespace App\DataFixtures;

use App\Entity\BiologicalResult;
use App\Entity\Consultation;
use App\Entity\Donor;
use App\Entity\MedicalHistory;
use App\Entity\Patient;
use App\Entity\TherapeuticEducation;
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
        $this->loadUsers($manager);
        $this->loadPatients($manager);

        $manager->flush();

        // Load medical data after flush so patient IDs are available
        $this->loadMedicalData($manager);
        $this->loadDonors($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        // Admin user: Sam Gamegie (technical admin, CHU practitioner)
        $admin = new User();
        $admin->setName('Sam');
        $admin->setSurname('Gamegie');
        $admin->setEmail('admin@admin.fr');
        $admin->setRoles(['ROLE_TECH_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setCristalId('CRISTAL-ADMIN-001');
        $admin->setIsChuPractitioner(false);
        $manager->persist($admin);

        // Medical admin user: Dr. Sophie Martin (senior doctor with admin privileges)
        $medicalAdmin = new User();
        $medicalAdmin->setName('Sophie');
        $medicalAdmin->setSurname('Martin');
        $medicalAdmin->setEmail('admin-medical@chu.fr');
        $medicalAdmin->setRoles(['ROLE_MEDICAL_ADMIN']);
        $medicalAdmin->setPassword($this->passwordHasher->hashPassword($medicalAdmin, 'password'));
        $medicalAdmin->setCristalId('CRISTAL-MADM-001');
        $medicalAdmin->setIsChuPractitioner(true);
        $manager->persist($medicalAdmin);

        // Doctor user: John Doe (CHU practitioner)
        $doctor = new User();
        $doctor->setName('John');
        $doctor->setSurname('Doe');
        $doctor->setEmail('docteur@chu.fr');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $doctor->setPassword($this->passwordHasher->hashPassword($doctor, 'password'));
        $doctor->setCristalId('CRISTAL-DOC-001');
        $doctor->setIsChuPractitioner(true);
        $manager->persist($doctor);

        // Nurse user: Marie Curie (CHU practitioner, read-only access - can view but not modify)
        $nurse = new User();
        $nurse->setName('Marie');
        $nurse->setSurname('Curie');
        $nurse->setEmail('infirmiere@chu.fr');
        $nurse->setRoles(['ROLE_NURSE']);
        $nurse->setPassword($this->passwordHasher->hashPassword($nurse, 'password'));
        $nurse->setCristalId('CRISTAL-INF-001');
        $nurse->setIsChuPractitioner(true);
        $manager->persist($nurse);

        // External city nephrologist: Dr. Lucie Vasseur (NOT CHU, only sees assigned patients)
        $externalDoctor = new User();
        $externalDoctor->setName('Lucie');
        $externalDoctor->setSurname('Vasseur');
        $externalDoctor->setEmail('nephrologue@ville.fr');
        $externalDoctor->setRoles(['ROLE_DOCTOR']);
        $externalDoctor->setPassword($this->passwordHasher->hashPassword($externalDoctor, 'password'));
        $externalDoctor->setCristalId('CRISTAL-EXT-001');
        $externalDoctor->setIsChuPractitioner(false);
        $manager->persist($externalDoctor);
        $this->addReference('external-doctor', $externalDoctor);

        // Patient user: Jean Dupont
        $patient = new User();
        $patient->setName('Jean');
        $patient->setSurname('Dupont');
        $patient->setEmail('patient@email.fr');
        $patient->setRoles(['ROLE_PATIENT']);
        $patient->setPassword($this->passwordHasher->hashPassword($patient, 'password'));
        $manager->persist($patient);

        // Disabled user: for testing account deactivation
        $disabled = new User();
        $disabled->setName('Compte');
        $disabled->setSurname('Désactivé');
        $disabled->setEmail('disabled@test.fr');
        $disabled->setRoles(['ROLE_USER']);
        $disabled->setPassword($this->passwordHasher->hashPassword($disabled, 'password'));
        $disabled->setIsActive(false);
        $manager->persist($disabled);

        // Test user: Benjamin Baillard (for testing password reset)
        $benjamin = new User();
        $benjamin->setName('Benjamin');
        $benjamin->setSurname('Baillard');
        $benjamin->setEmail('baillard.bjm2@orange.fr');
        $benjamin->setRoles(['ROLE_DOCTOR']);
        $benjamin->setPassword($this->passwordHasher->hashPassword($benjamin, 'password'));
        $benjamin->setIsChuPractitioner(true);
        $manager->persist($benjamin);
    }

    private function loadPatients(ObjectManager $manager): void
    {
        /** @var User $externalDoctor */
        $externalDoctor = $this->getReference('external-doctor', User::class);

        $patientsData = [
            // Paris region
            ['fileNumber' => '2024-001', 'lastName' => 'Martin', 'firstName' => 'Pierre', 'city' => 'Paris', 'birthDate' => '1965-03-15', 'sex' => 'M', 'bloodGroup' => 'A', 'rhesus' => '+', 'phone' => '06 12 34 56 78', 'email' => 'pierre.martin@email.fr'],
            ['fileNumber' => '2024-002', 'lastName' => 'Dupont', 'firstName' => 'Marie', 'city' => 'Paris', 'birthDate' => '1978-07-22', 'sex' => 'F', 'bloodGroup' => 'O', 'rhesus' => '+', 'phone' => '06 23 45 67 89', 'email' => 'marie.dupont@email.fr'],
            ['fileNumber' => '2024-010', 'lastName' => 'Fournier', 'firstName' => 'Isabelle', 'city' => 'Paris', 'birthDate' => '1982-01-28', 'sex' => 'F', 'bloodGroup' => 'AB', 'rhesus' => '-'],
            ['fileNumber' => '2024-011', 'lastName' => 'Leroy', 'firstName' => 'Antoine', 'city' => 'Paris', 'birthDate' => '1970-05-10', 'sex' => 'M', 'bloodGroup' => 'A', 'rhesus' => '-'],
            ['fileNumber' => '2024-012', 'lastName' => 'Dubois', 'firstName' => 'Nathalie', 'city' => 'Versailles', 'birthDate' => '1983-09-14', 'sex' => 'F', 'bloodGroup' => 'B', 'rhesus' => '+'],
            ['fileNumber' => '2024-013', 'lastName' => 'Lambert', 'firstName' => 'Christophe', 'city' => 'Boulogne-Billancourt', 'birthDate' => '1958-12-03', 'sex' => 'M', 'bloodGroup' => 'O', 'rhesus' => '-'],
            ['fileNumber' => '2024-014', 'lastName' => 'Girard', 'firstName' => 'Sylvie', 'city' => 'Saint-Denis', 'birthDate' => '1975-04-28', 'sex' => 'F', 'bloodGroup' => 'A', 'rhesus' => '+'],
            ['fileNumber' => '2024-015', 'lastName' => 'Bonnet', 'firstName' => 'Alain', 'city' => 'Argenteuil', 'birthDate' => '1962-08-19', 'sex' => 'M', 'bloodGroup' => 'AB', 'rhesus' => '+', 'comment' => 'Antécédent HTA'],

            // Lyon region
            ['fileNumber' => '2024-003', 'lastName' => 'Bernard', 'firstName' => 'Jean', 'city' => 'Lyon', 'birthDate' => '1952-11-08', 'sex' => 'M', 'bloodGroup' => 'B', 'rhesus' => '+', 'phone' => '06 34 56 78 90'],
            ['fileNumber' => '2024-016', 'lastName' => 'Rousseau', 'firstName' => 'Émilie', 'city' => 'Lyon', 'birthDate' => '1990-02-07', 'sex' => 'F', 'bloodGroup' => 'O', 'rhesus' => '-'],
            ['fileNumber' => '2024-017', 'lastName' => 'Vincent', 'firstName' => 'Mathieu', 'city' => 'Villeurbanne', 'birthDate' => '1967-06-30', 'sex' => 'M', 'bloodGroup' => 'A', 'rhesus' => '+'],
            ['fileNumber' => '2024-018', 'lastName' => 'Muller', 'firstName' => 'Catherine', 'city' => 'Vénissieux', 'birthDate' => '1979-11-22', 'sex' => 'F', 'bloodGroup' => 'B', 'rhesus' => '-'],

            // Marseille region
            ['fileNumber' => '2024-004', 'lastName' => 'Petit', 'firstName' => 'Claire', 'city' => 'Marseille', 'birthDate' => '1989-02-14', 'sex' => 'F', 'bloodGroup' => 'AB', 'rhesus' => '+'],
            ['fileNumber' => '2024-019', 'lastName' => 'Guerin', 'firstName' => 'Patrick', 'city' => 'Marseille', 'birthDate' => '1955-03-25', 'sex' => 'M', 'bloodGroup' => 'O', 'rhesus' => '+', 'comment' => 'Deuxième greffe rénale'],
            ['fileNumber' => '2024-020', 'lastName' => 'Mercier', 'firstName' => 'Sandrine', 'city' => 'Aix-en-Provence', 'birthDate' => '1984-07-16', 'sex' => 'F', 'bloodGroup' => 'A', 'rhesus' => '-'],
            ['fileNumber' => '2024-021', 'lastName' => 'Blanc', 'firstName' => 'Didier', 'city' => 'Aubagne', 'birthDate' => '1969-10-02', 'sex' => 'M', 'bloodGroup' => 'B', 'rhesus' => '+'],

            // Toulouse region
            ['fileNumber' => '2024-005', 'lastName' => 'Robert', 'firstName' => 'Michel', 'city' => 'Toulouse', 'birthDate' => '1971-09-30', 'sex' => 'M', 'bloodGroup' => 'A', 'rhesus' => '+', 'comment' => 'Patient diabétique, suivi particulier nécessaire'],
            ['fileNumber' => '2024-022', 'lastName' => 'Fabre', 'firstName' => 'Véronique', 'city' => 'Toulouse', 'birthDate' => '1976-01-12', 'sex' => 'F', 'bloodGroup' => 'O', 'rhesus' => '-'],
            ['fileNumber' => '2024-023', 'lastName' => 'Andre', 'firstName' => 'Olivier', 'city' => 'Blagnac', 'birthDate' => '1964-08-05', 'sex' => 'M', 'bloodGroup' => 'AB', 'rhesus' => '+'],

            // Nice region
            ['fileNumber' => '2024-006', 'lastName' => 'Lefebvre', 'firstName' => 'Sophie', 'city' => 'Nice', 'birthDate' => '1985-06-18', 'sex' => 'F', 'bloodGroup' => 'O', 'rhesus' => '+'],
            ['fileNumber' => '2024-024', 'lastName' => 'Picard', 'firstName' => 'Laurent', 'city' => 'Nice', 'birthDate' => '1957-04-08', 'sex' => 'M', 'bloodGroup' => 'A', 'rhesus' => '-'],
            ['fileNumber' => '2024-025', 'lastName' => 'Simon', 'firstName' => 'Martine', 'city' => 'Cannes', 'birthDate' => '1972-12-20', 'sex' => 'F', 'bloodGroup' => 'B'],
            ['fileNumber' => '2024-026', 'lastName' => 'Michel', 'firstName' => 'Éric', 'city' => 'Antibes', 'birthDate' => '1980-09-03', 'sex' => 'M', 'bloodGroup' => 'O'],

            // Nantes region
            ['fileNumber' => '2024-007', 'lastName' => 'Moreau', 'firstName' => 'François', 'city' => 'Nantes', 'birthDate' => '1960-12-05', 'sex' => 'M', 'bloodGroup' => 'B'],
            ['fileNumber' => '2024-027', 'lastName' => 'Lefevre', 'firstName' => 'Brigitte', 'city' => 'Nantes', 'birthDate' => '1968-07-14', 'sex' => 'F', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-028', 'lastName' => 'Legrand', 'firstName' => 'Thierry', 'city' => 'Saint-Nazaire', 'birthDate' => '1959-02-28', 'sex' => 'M', 'bloodGroup' => 'AB'],

            // Strasbourg region
            ['fileNumber' => '2024-008', 'lastName' => 'Garcia', 'firstName' => 'Carmen', 'city' => 'Strasbourg', 'birthDate' => '1973-04-25', 'sex' => 'F', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-029', 'lastName' => 'Schmitt', 'firstName' => 'Hans', 'city' => 'Strasbourg', 'birthDate' => '1966-11-17', 'sex' => 'M', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-030', 'lastName' => 'Meyer', 'firstName' => 'Anne', 'city' => 'Colmar', 'birthDate' => '1987-05-09', 'sex' => 'F', 'bloodGroup' => 'B'],

            // Bordeaux region
            ['fileNumber' => '2024-009', 'lastName' => 'Roux', 'firstName' => 'Philippe', 'city' => 'Bordeaux', 'birthDate' => '1968-08-12', 'sex' => 'M', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-031', 'lastName' => 'Durand', 'firstName' => 'Céline', 'city' => 'Bordeaux', 'birthDate' => '1981-03-06', 'sex' => 'F', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-032', 'lastName' => 'Morel', 'firstName' => 'Jacques', 'city' => 'Mérignac', 'birthDate' => '1954-10-31', 'sex' => 'M', 'bloodGroup' => 'B', 'comment' => 'Insuffisance rénale chronique stade 5'],

            // Lille region
            ['fileNumber' => '2024-033', 'lastName' => 'Lemaire', 'firstName' => 'Nicolas', 'city' => 'Lille', 'birthDate' => '1974-06-23', 'sex' => 'M', 'bloodGroup' => 'AB'],
            ['fileNumber' => '2024-034', 'lastName' => 'Fontaine', 'firstName' => 'Audrey', 'city' => 'Lille', 'birthDate' => '1992-01-15', 'sex' => 'F', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-035', 'lastName' => 'Chevalier', 'firstName' => 'Bruno', 'city' => 'Roubaix', 'birthDate' => '1963-09-08', 'sex' => 'M', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-036', 'lastName' => 'Robin', 'firstName' => 'Corinne', 'city' => 'Tourcoing', 'birthDate' => '1977-04-30', 'sex' => 'F', 'bloodGroup' => 'B'],

            // Montpellier region
            ['fileNumber' => '2024-037', 'lastName' => 'Masson', 'firstName' => 'David', 'city' => 'Montpellier', 'birthDate' => '1970-08-27', 'sex' => 'M', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-038', 'lastName' => 'Sanchez', 'firstName' => 'Maria', 'city' => 'Montpellier', 'birthDate' => '1986-12-11', 'sex' => 'F', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-039', 'lastName' => 'Clement', 'firstName' => 'Stéphane', 'city' => 'Béziers', 'birthDate' => '1961-05-19', 'sex' => 'M', 'bloodGroup' => 'AB'],

            // Rennes region
            ['fileNumber' => '2024-040', 'lastName' => 'Gauthier', 'firstName' => 'Hélène', 'city' => 'Rennes', 'birthDate' => '1983-02-22', 'sex' => 'F', 'bloodGroup' => 'B'],
            ['fileNumber' => '2024-041', 'lastName' => 'Perrin', 'firstName' => 'Yves', 'city' => 'Rennes', 'birthDate' => '1956-07-04', 'sex' => 'M', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-042', 'lastName' => 'Henry', 'firstName' => 'Monique', 'city' => 'Saint-Malo', 'birthDate' => '1969-11-29', 'sex' => 'F', 'bloodGroup' => 'A'],

            // Other cities
            ['fileNumber' => '2024-043', 'lastName' => 'Renard', 'firstName' => 'Gilles', 'city' => 'Grenoble', 'birthDate' => '1965-01-17', 'sex' => 'M', 'bloodGroup' => 'B'],
            ['fileNumber' => '2024-044', 'lastName' => 'Riviere', 'firstName' => 'Pascale', 'city' => 'Dijon', 'birthDate' => '1978-06-08', 'sex' => 'F', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-045', 'lastName' => 'Arnaud', 'firstName' => 'Marc', 'city' => 'Angers', 'birthDate' => '1972-10-13', 'sex' => 'M', 'bloodGroup' => 'A'],
            ['fileNumber' => '2024-046', 'lastName' => 'Martinez', 'firstName' => 'Rosa', 'city' => 'Perpignan', 'birthDate' => '1960-04-02', 'sex' => 'F', 'bloodGroup' => 'AB'],
            ['fileNumber' => '2024-047', 'lastName' => 'Colin', 'firstName' => 'Julien', 'city' => 'Reims', 'birthDate' => '1988-08-25', 'sex' => 'M', 'bloodGroup' => 'O'],
            ['fileNumber' => '2024-048', 'lastName' => 'Vidal', 'firstName' => 'Christine', 'city' => 'Le Havre', 'birthDate' => '1975-12-06', 'sex' => 'F', 'bloodGroup' => 'B'],
            ['fileNumber' => '2024-049', 'lastName' => 'Caron', 'firstName' => 'Raymond', 'city' => 'Saint-Étienne', 'birthDate' => '1951-03-21', 'sex' => 'M', 'bloodGroup' => 'A', 'comment' => 'Patient âgé, surveillance rapprochée'],
            ['fileNumber' => '2024-050', 'lastName' => 'Meunier', 'firstName' => 'Valérie', 'city' => 'Toulon', 'birthDate' => '1984-09-18', 'sex' => 'F', 'bloodGroup' => 'O'],
        ];

        foreach ($patientsData as $data) {
            $patient = new Patient();
            $patient->setFileNumber($data['fileNumber']);
            $patient->setLastName($data['lastName']);
            $patient->setFirstName($data['firstName']);
            $patient->setCity($data['city']);

            if (isset($data['birthDate'])) {
                $patient->setBirthDate(new \DateTime($data['birthDate']));
            }
            if (isset($data['sex'])) {
                $patient->setSex($data['sex']);
            }
            if (isset($data['bloodGroup'])) {
                $patient->setBloodGroup($data['bloodGroup']);
            }
            if (isset($data['rhesus'])) {
                $patient->setRhesus($data['rhesus']);
            }
            if (isset($data['phone'])) {
                $patient->setPhone($data['phone']);
            }
            if (isset($data['email'])) {
                $patient->setEmail($data['email']);
            }
            if (isset($data['comment'])) {
                $patient->setComment($data['comment']);
            }

            $manager->persist($patient);
        }

        // Assign first 5 named patients to the external nephrologist
        // (simulates city nephrologist following specific patients post-transplant)
        $assignedPatients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-001', '2024-002', '2024-003', '2024-004', '2024-005']]
        );
        foreach ($assignedPatients as $assignedPatient) {
            $assignedPatient->addAuthorizedPractitioner($externalDoctor);
        }

        // Generate 300 additional Paris patients to test >200 results confirmation
        $parisFirstNames = ['Adrien', 'Alexandre', 'Alice', 'Amélie', 'Antoine', 'Arnaud', 'Arthur', 'Aurélie', 'Baptiste', 'Bastien', 'Béatrice', 'Benjamin', 'Camille', 'Cédric', 'Charlotte', 'Clara', 'Clément', 'Damien', 'Diane', 'Élise', 'Émile', 'Emma', 'Fabien', 'Florian', 'Gabriel', 'Guillaume', 'Hugo', 'Inès', 'Jade', 'Jules', 'Julie', 'Karine', 'Léa', 'Léo', 'Louis', 'Lucas', 'Lucie', 'Manon', 'Margaux', 'Mathilde', 'Maxime', 'Nathan', 'Nina', 'Noah', 'Noémie', 'Océane', 'Paul', 'Raphaël', 'Romain', 'Sarah', 'Simon', 'Théo', 'Thomas', 'Valentin', 'Valentine', 'Victor', 'Zoé', 'Yann', 'Xavier', 'Quentin'];
        $parisLastNames = ['Adam', 'Aubry', 'Barbier', 'Baron', 'Berger', 'Bertrand', 'Blanchard', 'Boucher', 'Brun', 'Carpentier', 'Chartier', 'Collet', 'Cordier', 'Coulon', 'David', 'Delorme', 'Denis', 'Descamps', 'Dufour', 'Dupuis', 'Etienne', 'Ferry', 'Fleury', 'Garnier', 'Gérard', 'Giraud', 'Grondin', 'Guillot', 'Hardy', 'Hubert', 'Jacob', 'Joly', 'Klein', 'Lacroix', 'Laurent', 'Leclerc', 'Lemoine', 'Leroux', 'Loiseau', 'Louis', 'Marchand', 'Marie', 'Martel', 'Mathieu', 'Menard', 'Monnier', 'Moulin', 'Noel', 'Olivier', 'Paris', 'Pascal', 'Pelletier', 'Pichon', 'Poirier', 'Raymond', 'Regnier', 'Rey', 'Rolland', 'Roussel', 'Roy'];
        $bloodGroups = ['A', 'B', 'AB', 'O'];
        $rhesusValues = ['+', '-'];

        for ($i = 1; $i <= 300; $i++) {
            $sex = $i % 2 === 0 ? 'F' : 'M';
            $firstName = $parisFirstNames[$i % count($parisFirstNames)];
            $lastName = $parisLastNames[$i % count($parisLastNames)];
            $bloodGroup = $bloodGroups[$i % count($bloodGroups)];
            $rhesus = $rhesusValues[$i % count($rhesusValues)];
            $year = 1950 + ($i % 45);
            $month = ($i % 12) + 1;
            $day = ($i % 28) + 1;

            $patient = new Patient();
            $patient->setFileNumber(sprintf('2025-%04d', $i));
            $patient->setLastName($lastName);
            $patient->setFirstName($firstName);
            $patient->setCity('Paris');
            $patient->setBirthDate(new \DateTime(sprintf('%d-%02d-%02d', $year, $month, $day)));
            $patient->setSex($sex);
            $patient->setBloodGroup($bloodGroup);
            $patient->setRhesus($rhesus);

            $manager->persist($patient);
        }
    }

    private function loadMedicalData(ObjectManager $manager): void
    {
        $patients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-001', '2024-002', '2024-003', '2024-004', '2024-005']]
        );

        foreach ($patients as $patient) {
            $this->loadMedicalHistoryFor($manager, $patient);
            $this->loadConsultationsFor($manager, $patient);
            $this->loadTherapeuticEducationFor($manager, $patient);
            $this->loadBiologicalResultsFor($manager, $patient);
        }
    }

    private function loadMedicalHistoryFor(ObjectManager $manager, Patient $patient): void
    {
        $entries = [
            ['type' => 'Médical', 'description' => 'Hypertension artérielle traitée par inhibiteurs calciques', 'diagnosisDate' => '2015-06-10', 'comment' => 'Bien contrôlée sous traitement'],
            ['type' => 'Médical', 'description' => 'Insuffisance rénale chronique stade 5, étiologie glomérulonéphrite', 'diagnosisDate' => '2018-03-20'],
            ['type' => 'Chirurgical', 'description' => 'Pose de fistule artério-veineuse au bras gauche', 'diagnosisDate' => '2019-01-15'],
            ['type' => 'Allergique', 'description' => 'Allergie documentée à la pénicilline (éruption cutanée)', 'diagnosisDate' => '2010-09-05', 'comment' => 'Utiliser macrolides en alternative'],
            ['type' => 'Familial', 'description' => 'Père décédé d\'insuffisance rénale à 62 ans, mère diabétique type 2'],
        ];

        foreach ($entries as $data) {
            $history = new MedicalHistory();
            $history->setPatient($patient);
            $history->setType($data['type']);
            $history->setDescription($data['description']);
            if (isset($data['diagnosisDate'])) {
                $history->setDiagnosisDate(new \DateTime($data['diagnosisDate']));
            }
            if (isset($data['comment'])) {
                $history->setComment($data['comment']);
            }
            $manager->persist($history);
        }
    }

    private function loadConsultationsFor(ObjectManager $manager, Patient $patient): void
    {
        $entries = [
            ['date' => '2025-01-15', 'practitionerName' => 'Dr. Martin Sophie', 'type' => 'Suivi post-greffe', 'observations' => 'Bonne évolution clinique. Créatinine stable. Pas de signe de rejet.', 'treatmentNotes' => 'Maintien du tacrolimus à 5mg/j', 'nextAppointmentDate' => '2025-02-15'],
            ['date' => '2025-02-15', 'practitionerName' => 'Dr. Martin Sophie', 'type' => 'Suivi post-greffe', 'observations' => 'Patient en bon état général. Tension artérielle bien contrôlée 130/80. Greffon fonctionnel.', 'nextAppointmentDate' => '2025-03-15'],
            ['date' => '2024-12-01', 'practitionerName' => 'Dr. Doe John', 'type' => 'Contrôle', 'observations' => 'Contrôle annuel. Bilan complet demandé. Échographie du greffon sans anomalie.'],
            ['date' => '2024-10-20', 'practitionerName' => 'Dr. Vasseur Lucie', 'type' => 'Suivi post-greffe', 'observations' => 'Suivi néphrologue de ville. Fonction rénale stable. Poursuite du traitement immunosuppresseur.', 'treatmentNotes' => 'Réduction progressive des corticoïdes'],
        ];

        foreach ($entries as $data) {
            $consultation = new Consultation();
            $consultation->setPatient($patient);
            $consultation->setDate(new \DateTime($data['date']));
            $consultation->setPractitionerName($data['practitionerName']);
            $consultation->setType($data['type']);
            $consultation->setObservations($data['observations']);
            if (isset($data['treatmentNotes'])) {
                $consultation->setTreatmentNotes($data['treatmentNotes']);
            }
            if (isset($data['nextAppointmentDate'])) {
                $consultation->setNextAppointmentDate(new \DateTime($data['nextAppointmentDate']));
            }
            $manager->persist($consultation);
        }
    }

    private function loadTherapeuticEducationFor(ObjectManager $manager, Patient $patient): void
    {
        $entries = [
            ['sessionDate' => '2025-01-20', 'topic' => 'Observance médicamenteuse', 'educator' => 'Mme Curie Marie', 'objectives' => 'Comprendre l\'importance de la prise régulière des immunosuppresseurs', 'observations' => 'Patient attentif, bonne compréhension', 'patientProgress' => 'Acquis', 'nextSessionDate' => '2025-02-20'],
            ['sessionDate' => '2025-02-20', 'topic' => 'Signes de rejet', 'educator' => 'Mme Curie Marie', 'objectives' => 'Savoir identifier les signes précoces de rejet du greffon', 'observations' => 'Patient capable de citer les principaux signes d\'alerte', 'patientProgress' => 'En cours', 'nextSessionDate' => '2025-03-20'],
            ['sessionDate' => '2024-12-10', 'topic' => 'Hygiène de vie', 'educator' => 'M. Laurent Pierre', 'objectives' => 'Adapter l\'alimentation et l\'activité physique post-greffe', 'observations' => 'Conseils diététiques donnés, programme d\'activité physique établi', 'patientProgress' => 'En cours'],
            ['sessionDate' => '2024-11-05', 'topic' => 'Diététique', 'educator' => 'Mme Blanc Sophie', 'objectives' => 'Régime pauvre en sel et suivi des apports hydriques', 'patientProgress' => 'Acquis'],
        ];

        foreach ($entries as $data) {
            $session = new TherapeuticEducation();
            $session->setPatient($patient);
            $session->setSessionDate(new \DateTime($data['sessionDate']));
            $session->setTopic($data['topic']);
            $session->setEducator($data['educator']);
            if (isset($data['objectives'])) {
                $session->setObjectives($data['objectives']);
            }
            if (isset($data['observations'])) {
                $session->setObservations($data['observations']);
            }
            if (isset($data['patientProgress'])) {
                $session->setPatientProgress($data['patientProgress']);
            }
            if (isset($data['nextSessionDate'])) {
                $session->setNextSessionDate(new \DateTime($data['nextSessionDate']));
            }
            $manager->persist($session);
        }
    }

    private function loadBiologicalResultsFor(ObjectManager $manager, Patient $patient): void
    {
        $entries = [
            ['date' => '2025-02-15', 'creatinine' => 125.0, 'creatinineClearance' => 62.5, 'proteinuria' => 0.12, 'hemoglobin' => 12.8, 'whiteBloodCells' => 6.5, 'platelets' => 245.0, 'tacrolimusLevel' => 7.2, 'cmvPcr' => 'Négatif', 'ebvPcr' => 'Négatif'],
            ['date' => '2025-01-15', 'creatinine' => 130.0, 'creatinineClearance' => 60.0, 'proteinuria' => 0.15, 'hemoglobin' => 12.5, 'whiteBloodCells' => 7.0, 'platelets' => 230.0, 'tacrolimusLevel' => 8.1, 'cmvPcr' => 'Négatif', 'ebvPcr' => 'Non effectué'],
            ['date' => '2024-12-01', 'creatinine' => 135.0, 'creatinineClearance' => 58.0, 'proteinuria' => 0.18, 'hemoglobin' => 11.9, 'whiteBloodCells' => 7.8, 'platelets' => 220.0, 'tacrolimusLevel' => 9.5, 'ciclosporinLevel' => null, 'cmvPcr' => 'Négatif', 'ebvPcr' => 'Négatif', 'comment' => 'Légère augmentation de la créatinine, à surveiller'],
            ['date' => '2024-10-20', 'creatinine' => 128.0, 'creatinineClearance' => 61.0, 'proteinuria' => 0.10, 'hemoglobin' => 13.0, 'whiteBloodCells' => 6.2, 'platelets' => 260.0, 'tacrolimusLevel' => 7.8, 'cmvPcr' => 'Négatif'],
        ];

        foreach ($entries as $data) {
            $result = new BiologicalResult();
            $result->setPatient($patient);
            $result->setDate(new \DateTime($data['date']));
            if (isset($data['creatinine'])) { $result->setCreatinine($data['creatinine']); }
            if (isset($data['creatinineClearance'])) { $result->setCreatinineClearance($data['creatinineClearance']); }
            if (isset($data['proteinuria'])) { $result->setProteinuria($data['proteinuria']); }
            if (isset($data['hemoglobin'])) { $result->setHemoglobin($data['hemoglobin']); }
            if (isset($data['whiteBloodCells'])) { $result->setWhiteBloodCells($data['whiteBloodCells']); }
            if (isset($data['platelets'])) { $result->setPlatelets($data['platelets']); }
            if (isset($data['tacrolimusLevel'])) { $result->setTacrolimusLevel($data['tacrolimusLevel']); }
            if (isset($data['ciclosporinLevel'])) { $result->setCiclosporinLevel($data['ciclosporinLevel']); }
            if (isset($data['cmvPcr'])) { $result->setCmvPcr($data['cmvPcr']); }
            if (isset($data['ebvPcr'])) { $result->setEbvPcr($data['ebvPcr']); }
            if (isset($data['comment'])) { $result->setComment($data['comment']); }
            $manager->persist($result);
        }
    }

    private function loadDonors(ObjectManager $manager): void
    {
        $donorsData = [
            // Living donors
            [
                'donorType' => Donor::TYPE_LIVING,
                'cristalNumber' => 'CRI-2025-V001',
                'bloodGroup' => 'A',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 42,
                'height' => 165,
                'weight' => 62,
                'lastName' => 'Martin',
                'firstName' => 'Claire',
                'relationshipType' => 'Conjoint',
                'creatinine' => '72.00',
                'isotopicClearance' => '95.50',
                'proteinuria' => '0.08',
                'approach' => 'Cœlioscopie',
                'robot' => true,
                'hlaA' => 2, 'hlaB' => 7, 'hlaCw' => 4, 'hlaDR' => 11, 'hlaDQ' => 3, 'hlaDP' => 1,
                'cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+',
                'donorSurgeonName' => 'Dr. Legrand',
                'clampingDate' => '2025-03-10',
                'donorHarvestSide' => 'gauche',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'Celsior',
            ],
            [
                'donorType' => Donor::TYPE_LIVING,
                'cristalNumber' => 'CRI-2025-V002',
                'bloodGroup' => 'O',
                'rhesus' => '-',
                'sex' => 'M',
                'age' => 38,
                'height' => 178,
                'weight' => 80,
                'lastName' => 'Dupont',
                'firstName' => 'Michel',
                'relationshipType' => 'Parent',
                'creatinine' => '85.00',
                'isotopicClearance' => '102.30',
                'proteinuria' => '0.05',
                'approach' => 'Lombotomie',
                'robot' => false,
                'hlaA' => 1, 'hlaB' => 8, 'hlaDR' => 15, 'hlaDQ' => 6, 'hlaDP' => null,
                'hlaCw' => null,
                'cmv' => '-', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '-', 'acHbc' => '-', 'toxoplasmosis' => '-',
                'donorSurgeonName' => 'Dr. Moreau',
                'clampingDate' => '2025-02-20',
                'donorHarvestSide' => 'droit',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'Viaspan',
            ],
            [
                'donorType' => Donor::TYPE_LIVING,
                'cristalNumber' => 'CRI-2025-V003',
                'bloodGroup' => 'B',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 55,
                'height' => 160,
                'weight' => 68,
                'lastName' => 'Rousseau',
                'firstName' => 'Anne',
                'relationshipType' => '2ème degré',
                'creatinine' => '90.00',
                'isotopicClearance' => '88.00',
                'proteinuria' => '0.12',
                'approach' => 'Cœlioscopie',
                'robot' => true,
                'hlaA' => 3, 'hlaB' => 44, 'hlaCw' => 5, 'hlaDR' => 4, 'hlaDQ' => 8, 'hlaDP' => 2,
                'cmv' => '+', 'ebv' => '-', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '+', 'toxoplasmosis' => 'ND',
                'donorSurgeonName' => 'Dr. Petit',
                'clampingDate' => '2025-01-15',
                'donorHarvestSide' => 'gauche',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'IGL',
            ],

            // Deceased donors - encephalic
            [
                'donorType' => Donor::TYPE_DECEASED_ENCEPHALIC,
                'cristalNumber' => 'CRI-2025-D001',
                'bloodGroup' => 'A',
                'rhesus' => '+',
                'sex' => 'M',
                'age' => 52,
                'height' => 175,
                'weight' => 78,
                'originCity' => 'Lyon',
                'deathCause' => 'AVC hémorragique',
                'deathCauseComment' => 'Hémorragie cérébrale massive, coma profond',
                'extendedCriteriaDonor' => true,
                'cardiacArrest' => false,
                'cardiacArrestDuration' => 0,
                'meanArterialPressure' => '75.0',
                'amines' => true,
                'transfusion' => true,
                'cgr' => 4,
                'cpa' => 1,
                'pfc' => 2,
                'creatinineArrival' => '98.00',
                'creatinineSample' => '112.00',
                'ureter' => '1',
                'conservationLiquid' => 'Celsior',
                'hlaA' => 2, 'hlaB' => 35, 'hlaCw' => 7, 'hlaDR' => 1, 'hlaDQ' => 5, 'hlaDP' => null,
                'cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+',
                'donorSurgeonName' => 'Dr. Bernard',
                'clampingDate' => '2025-03-05',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '1 artère principale',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'Celsior',
                'aortaAtheroma' => true,
                'calcifiedAortaPlaques' => false,
                'renalArteryAtheroma' => false,
            ],
            [
                'donorType' => Donor::TYPE_DECEASED_ENCEPHALIC,
                'cristalNumber' => 'CRI-2025-D002',
                'bloodGroup' => 'O',
                'rhesus' => '-',
                'sex' => 'F',
                'age' => 45,
                'height' => 162,
                'weight' => 58,
                'originCity' => 'Marseille',
                'deathCause' => 'Anoxie',
                'deathCauseComment' => 'Arrêt cardiaque récupéré puis mort encéphalique',
                'extendedCriteriaDonor' => false,
                'cardiacArrest' => true,
                'cardiacArrestDuration' => 15,
                'meanArterialPressure' => '82.0',
                'amines' => false,
                'transfusion' => false,
                'creatinineArrival' => '75.00',
                'creatinineSample' => '88.00',
                'ureter' => '1',
                'conservationLiquid' => 'Viaspan',
                'hlaA' => 11, 'hlaB' => 27, 'hlaCw' => null, 'hlaDR' => 7, 'hlaDQ' => 2, 'hlaDP' => null,
                'cmv' => '-', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '-', 'acHbc' => '-', 'toxoplasmosis' => '-',
                'donorSurgeonName' => 'Dr. Girard',
                'clampingDate' => '2025-02-28',
                'donorHarvestSide' => 'gauche',
                'mainArtery' => '1 artère principale',
                'upperPolarArtery' => '1 polaire supérieure',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'Viaspan',
            ],
            [
                'donorType' => Donor::TYPE_DECEASED_ENCEPHALIC,
                'cristalNumber' => 'CRI-2025-D003',
                'bloodGroup' => 'AB',
                'rhesus' => '+',
                'sex' => 'M',
                'age' => 63,
                'height' => 180,
                'weight' => 92,
                'originCity' => 'Bordeaux',
                'deathCause' => 'AVC ischémique',
                'deathCauseComment' => 'AVC ischémique massif hémisphère droit',
                'extendedCriteriaDonor' => true,
                'cardiacArrest' => false,
                'cardiacArrestDuration' => 0,
                'meanArterialPressure' => '68.0',
                'amines' => true,
                'transfusion' => true,
                'cgr' => 6,
                'cpa' => 2,
                'pfc' => 3,
                'creatinineArrival' => '140.00',
                'creatinineSample' => '155.00',
                'ureter' => '2',
                'conservationLiquid' => 'IGL',
                'hlaA' => 24, 'hlaB' => 51, 'hlaCw' => 1, 'hlaDR' => 13, 'hlaDQ' => 6, 'hlaDP' => 4,
                'cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '+', 'toxoplasmosis' => '+',
                'donorSurgeonName' => 'Dr. Duval',
                'clampingDate' => '2025-01-22',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '2 artères',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'IGL',
                'aortaAtheroma' => true,
                'calcifiedAortaPlaques' => true,
                'ostiumArteryAtheroma' => true,
                'renalArteryAtheroma' => false,
            ],

            // Deceased donor - cardiac arrest
            [
                'donorType' => Donor::TYPE_DECEASED_CARDIAC_ARREST,
                'cristalNumber' => 'CRI-2025-C001',
                'bloodGroup' => 'B',
                'rhesus' => '-',
                'sex' => 'M',
                'age' => 48,
                'height' => 172,
                'weight' => 75,
                'originCity' => 'Toulouse',
                'deathCause' => 'AVP',
                'deathCauseComment' => 'Accident de la voie publique, traumatisme thoracique sévère',
                'extendedCriteriaDonor' => false,
                'cardiacArrest' => true,
                'cardiacArrestDuration' => 8,
                'meanArterialPressure' => '90.0',
                'amines' => true,
                'transfusion' => true,
                'cgr' => 8,
                'cpa' => 2,
                'pfc' => 4,
                'creatinineArrival' => '110.00',
                'creatinineSample' => '130.00',
                'ureter' => '1',
                'conservationLiquid' => 'Scott',
                'hlaA' => 29, 'hlaB' => 13, 'hlaCw' => 6, 'hlaDR' => 17, 'hlaDQ' => 9, 'hlaDP' => null,
                'cmv' => '-', 'ebv' => '-', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-',
                'donorSurgeonName' => 'Dr. Lambert',
                'clampingDate' => '2025-03-01',
                'donorHarvestSide' => 'gauche',
                'mainArtery' => '1 artère rénale',
                'vein' => '1 veine rénale',
                'veinComment' => 'Veine courte, anastomose sur VCI',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'Scott',
                'digestiveWound' => true,
            ],
            [
                'donorType' => Donor::TYPE_DECEASED_CARDIAC_ARREST,
                'cristalNumber' => 'CRI-2025-C002',
                'bloodGroup' => 'O',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 35,
                'height' => 168,
                'weight' => 65,
                'originCity' => 'Nantes',
                'deathCause' => 'TC non AVP',
                'deathCauseComment' => 'Traumatisme crânien suite à une chute domestique',
                'extendedCriteriaDonor' => false,
                'cardiacArrest' => true,
                'cardiacArrestDuration' => 5,
                'meanArterialPressure' => '95.0',
                'amines' => false,
                'transfusion' => false,
                'creatinineArrival' => '65.00',
                'creatinineSample' => '72.00',
                'ureter' => '1',
                'conservationLiquid' => 'Celsior',
                'hlaA' => 32, 'hlaB' => 44, 'hlaCw' => 5, 'hlaDR' => 11, 'hlaDQ' => 7, 'hlaDP' => 2,
                'cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-',
                'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+',
                'donorSurgeonName' => 'Dr. Faure',
                'clampingDate' => '2025-02-14',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '1 artère rénale',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'Celsior',
            ],
        ];

        foreach ($donorsData as $data) {
            $donor = new Donor();
            $donor->setDonorType($data['donorType']);
            $donor->setCristalNumber($data['cristalNumber']);
            $donor->setBloodGroup($data['bloodGroup']);
            $donor->setRhesus($data['rhesus']);
            $donor->setSex($data['sex']);
            $donor->setAge($data['age']);
            if (isset($data['height'])) { $donor->setHeight($data['height']); }
            if (isset($data['weight'])) { $donor->setWeight($data['weight']); }

            // HLA
            $donor->setHlaA($data['hlaA']);
            $donor->setHlaB($data['hlaB']);
            if (isset($data['hlaCw'])) { $donor->setHlaCw($data['hlaCw']); }
            $donor->setHlaDR($data['hlaDR']);
            $donor->setHlaDQ($data['hlaDQ']);
            if (isset($data['hlaDP'])) { $donor->setHlaDP($data['hlaDP']); }

            // Serology
            $donor->setCmv($data['cmv']);
            $donor->setEbv($data['ebv']);
            $donor->setHiv($data['hiv']);
            $donor->setHtlv($data['htlv']);
            $donor->setSyphilis($data['syphilis']);
            $donor->setHcv($data['hcv']);
            $donor->setAgHbs($data['agHbs']);
            $donor->setAcHbs($data['acHbs']);
            $donor->setAcHbc($data['acHbc']);
            if (isset($data['toxoplasmosis'])) { $donor->setToxoplasmosis($data['toxoplasmosis']); }

            // Surgical
            if (isset($data['donorSurgeonName'])) { $donor->setDonorSurgeonName($data['donorSurgeonName']); }
            if (isset($data['clampingDate'])) { $donor->setClampingDate(new \DateTime($data['clampingDate'])); }
            if (isset($data['donorHarvestSide'])) { $donor->setDonorHarvestSide($data['donorHarvestSide']); }
            if (isset($data['mainArtery'])) { $donor->setMainArtery($data['mainArtery']); }
            if (isset($data['upperPolarArtery'])) { $donor->setUpperPolarArtery($data['upperPolarArtery']); }
            if (isset($data['lowerPolarArtery'])) { $donor->setLowerPolarArtery($data['lowerPolarArtery']); }
            if (isset($data['vein'])) { $donor->setVein($data['vein']); }
            if (isset($data['veinComment'])) { $donor->setVeinComment($data['veinComment']); }
            if (isset($data['perfusionMachine'])) { $donor->setPerfusionMachine($data['perfusionMachine']); }
            if (isset($data['perfusionLiquid'])) { $donor->setPerfusionLiquid($data['perfusionLiquid']); }

            // Living donor specific
            if (isset($data['lastName'])) { $donor->setLastName($data['lastName']); }
            if (isset($data['firstName'])) { $donor->setFirstName($data['firstName']); }
            if (isset($data['relationshipType'])) { $donor->setRelationshipType($data['relationshipType']); }
            if (isset($data['creatinine'])) { $donor->setCreatinine($data['creatinine']); }
            if (isset($data['isotopicClearance'])) { $donor->setIsotopicClearance($data['isotopicClearance']); }
            if (isset($data['proteinuria'])) { $donor->setProteinuria($data['proteinuria']); }
            if (isset($data['approach'])) { $donor->setApproach($data['approach']); }
            if (isset($data['robot'])) { $donor->setRobot($data['robot']); }

            // Deceased donor specific
            if (isset($data['originCity'])) { $donor->setOriginCity($data['originCity']); }
            if (isset($data['deathCause'])) { $donor->setDeathCause($data['deathCause']); }
            if (isset($data['deathCauseComment'])) { $donor->setDeathCauseComment($data['deathCauseComment']); }
            if (isset($data['extendedCriteriaDonor'])) { $donor->setExtendedCriteriaDonor($data['extendedCriteriaDonor']); }
            if (isset($data['cardiacArrest'])) { $donor->setCardiacArrest($data['cardiacArrest']); }
            if (isset($data['cardiacArrestDuration'])) { $donor->setCardiacArrestDuration($data['cardiacArrestDuration']); }
            if (isset($data['meanArterialPressure'])) { $donor->setMeanArterialPressure($data['meanArterialPressure']); }
            if (isset($data['amines'])) { $donor->setAmines($data['amines']); }
            if (isset($data['transfusion'])) { $donor->setTransfusion($data['transfusion']); }
            if (isset($data['cgr'])) { $donor->setCgr($data['cgr']); }
            if (isset($data['cpa'])) { $donor->setCpa($data['cpa']); }
            if (isset($data['pfc'])) { $donor->setPfc($data['pfc']); }
            if (isset($data['creatinineArrival'])) { $donor->setCreatinineArrival($data['creatinineArrival']); }
            if (isset($data['creatinineSample'])) { $donor->setCreatinineSample($data['creatinineSample']); }
            if (isset($data['ureter'])) { $donor->setUreter($data['ureter']); }
            if (isset($data['conservationLiquid'])) { $donor->setConservationLiquid($data['conservationLiquid']); }

            // Atheroma
            if (isset($data['aortaAtheroma'])) { $donor->setAortaAtheroma($data['aortaAtheroma']); }
            if (isset($data['calcifiedAortaPlaques'])) { $donor->setCalcifiedAortaPlaques($data['calcifiedAortaPlaques']); }
            if (isset($data['ostiumArteryAtheroma'])) { $donor->setOstiumArteryAtheroma($data['ostiumArteryAtheroma']); }
            if (isset($data['calcifiedOstiumPlaques'])) { $donor->setCalcifiedOstiumPlaques($data['calcifiedOstiumPlaques']); }
            if (isset($data['renalArteryAtheroma'])) { $donor->setRenalArteryAtheroma($data['renalArteryAtheroma']); }
            if (isset($data['calcifiedRenalPlaques'])) { $donor->setCalcifiedRenalPlaques($data['calcifiedRenalPlaques']); }
            if (isset($data['digestiveWound'])) { $donor->setDigestiveWound($data['digestiveWound']); }
            if (isset($data['conservationLiquidInfection'])) { $donor->setConservationLiquidInfection($data['conservationLiquidInfection']); }

            if (isset($data['patientComment'])) { $donor->setPatientComment($data['patientComment']); }

            $manager->persist($donor);
        }
    }
}
