<?php

namespace App\DataFixtures;

use App\Entity\BiologicalResult;
use App\Entity\Consultation;
use App\Entity\Donor;
use App\Entity\DonorHlaTyping;
use App\Entity\DonorSerology;
use App\Entity\MedicalHistory;
use App\Entity\Patient;
use App\Entity\Transplant;
use App\Entity\TransplantHlaIncompatibility;
use App\Entity\TransplantVirologicalStatus;
use App\Entity\TherapeuticEducation;
use App\Entity\User;
use App\Entity\Reference\BloodGroup;
use App\Entity\Reference\ConsultationType;
use App\Entity\Reference\DeathCause;
use App\Entity\Reference\DonorType as DonorTypeRef;
use App\Entity\Reference\EducationTopic;
use App\Entity\Reference\HlaLocus;
use App\Entity\Reference\ImmunologicalRisk;
use App\Entity\Reference\ImmunosuppressiveDrug;
use App\Entity\Reference\MedicalHistoryType;
use App\Entity\Reference\PatientProgress;
use App\Entity\Reference\PerfusionLiquid;
use App\Entity\Reference\PeritonealPosition;
use App\Entity\Reference\RelationshipType;
use App\Entity\Reference\SerologyMarker;
use App\Entity\Reference\SurgicalApproach;
use App\Entity\Reference\TransplantType;
use App\Entity\Reference\VirologicalMarker;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /** @var array<string, BloodGroup> */
    private array $bloodGroups = [];
    /** @var array<string, DonorTypeRef> */
    private array $donorTypes = [];
    /** @var array<string, ConsultationType> */
    private array $consultationTypes = [];
    /** @var array<string, TransplantType> */
    private array $transplantTypes = [];
    /** @var array<string, DeathCause> */
    private array $deathCauses = [];
    /** @var array<string, RelationshipType> */
    private array $relationshipTypes = [];
    /** @var array<string, EducationTopic> */
    private array $educationTopics = [];
    /** @var array<string, MedicalHistoryType> */
    private array $medicalHistoryTypes = [];
    /** @var array<string, ImmunologicalRisk> */
    private array $immunologicalRisks = [];
    /** @var array<string, ImmunosuppressiveDrug> */
    private array $immunosuppressiveDrugs = [];
    /** @var array<string, PerfusionLiquid> */
    private array $perfusionLiquids = [];
    /** @var array<string, PeritonealPosition> */
    private array $peritonealPositions = [];
    /** @var array<string, SurgicalApproach> */
    private array $surgicalApproaches = [];
    /** @var array<string, PatientProgress> */
    private array $patientProgressValues = [];
    /** @var array<string, HlaLocus> */
    private array $hlaLoci = [];
    /** @var array<string, SerologyMarker> */
    private array $serologyMarkers = [];
    /** @var array<string, VirologicalMarker> */
    private array $virologicalMarkers = [];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Reference tables must be loaded and flushed first
        $this->loadReferenceData($manager);
        $manager->flush();

        $this->loadUsers($manager);
        $this->loadPatients($manager);

        $manager->flush();

        // Assign practitioners to patients after flush so findBy works
        $this->loadPatientAssignments($manager);

        // Load medical data after flush so patient IDs are available
        $this->loadMedicalData($manager);
        $this->loadDonors($manager);

        $manager->flush();

        // Load transplants after donors are flushed so donor IDs are available
        $this->loadTransplants($manager);

        $manager->flush();
    }

    // ===================================================================
    // REFERENCE DATA
    // ===================================================================

    private function loadReferenceData(ObjectManager $manager): void
    {
        $this->loadBloodGroups($manager);
        $this->loadDonorTypes($manager);
        $this->loadConsultationTypes($manager);
        $this->loadTransplantTypes($manager);
        $this->loadDeathCauses($manager);
        $this->loadRelationshipTypes($manager);
        $this->loadEducationTopics($manager);
        $this->loadMedicalHistoryTypes($manager);
        $this->loadImmunologicalRisks($manager);
        $this->loadImmunosuppressiveDrugs($manager);
        $this->loadPerfusionLiquids($manager);
        $this->loadPeritonealPositions($manager);
        $this->loadSurgicalApproaches($manager);
        $this->loadPatientProgressValues($manager);
        $this->loadHlaLoci($manager);
        $this->loadSerologyMarkers($manager);
        $this->loadVirologicalMarkers($manager);
    }

    private function loadBloodGroups(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'A',  'label' => 'A',  'order' => 1],
            ['code' => 'B',  'label' => 'B',  'order' => 2],
            ['code' => 'AB', 'label' => 'AB', 'order' => 3],
            ['code' => 'O',  'label' => 'O',  'order' => 4],
        ];
        foreach ($data as $d) {
            $e = new BloodGroup();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->bloodGroups[$d['code']] = $e;
        }
    }

    private function loadDonorTypes(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'living',                'label' => 'Donneur vivant',                       'order' => 1],
            ['code' => 'deceased_encephalic',    'label' => 'Donneur décédé (mort encéphalique)',   'order' => 2],
            ['code' => 'deceased_cardiac_arrest', 'label' => 'Donneur décédé (arrêt cardiaque)',   'order' => 3],
        ];
        foreach ($data as $d) {
            $e = new DonorTypeRef();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->donorTypes[$d['code']] = $e;
        }
    }

    private function loadConsultationTypes(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'suivi_post_greffe', 'label' => 'Suivi post-greffe', 'order' => 1],
            ['code' => 'bilan_pre_greffe',  'label' => 'Bilan pré-greffe',  'order' => 2],
            ['code' => 'urgence',           'label' => 'Urgence',           'order' => 3],
            ['code' => 'controle',          'label' => 'Contrôle',          'order' => 4],
            ['code' => 'autre',             'label' => 'Autre',             'order' => 5],
        ];
        foreach ($data as $d) {
            $e = new ConsultationType();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->consultationTypes[$d['code']] = $e;
        }
    }

    private function loadTransplantTypes(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'rein',                  'label' => 'Rein',                   'order' => 1],
            ['code' => 'rein_donneur_vivant',   'label' => 'Rein donneur vivant',    'order' => 2],
            ['code' => 'rein_pancreas',         'label' => 'Rein-pancréas',          'order' => 3],
            ['code' => 'rein_foie',             'label' => 'Rein-foie',              'order' => 4],
            ['code' => 'rein_coeur',            'label' => 'Rein-cœur',              'order' => 5],
            ['code' => 'autre',                 'label' => 'Autre',                  'order' => 6],
        ];
        foreach ($data as $d) {
            $e = new TransplantType();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->transplantTypes[$d['code']] = $e;
        }
    }

    private function loadDeathCauses(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'avc_hemorragique', 'label' => 'AVC hémorragique', 'order' => 1],
            ['code' => 'avc_ischemique',   'label' => 'AVC ischémique',   'order' => 2],
            ['code' => 'avp',              'label' => 'AVP',              'order' => 3],
            ['code' => 'tc_non_avp',       'label' => 'TC non AVP',       'order' => 4],
            ['code' => 'anoxie',           'label' => 'Anoxie',           'order' => 5],
            ['code' => 'autre',            'label' => 'Autre',            'order' => 6],
        ];
        foreach ($data as $d) {
            $e = new DeathCause();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->deathCauses[$d['code']] = $e;
        }
    }

    private function loadRelationshipTypes(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'parent',          'label' => 'Parent',          'order' => 1],
            ['code' => 'enfant',          'label' => 'Enfant',          'order' => 2],
            ['code' => '2eme_degre',      'label' => '2ème degré',      'order' => 3],
            ['code' => 'conjoint',        'label' => 'Conjoint',        'order' => 4],
            ['code' => 'non_apparente',   'label' => 'Non apparenté',   'order' => 5],
            ['code' => 'autre',           'label' => 'Autre',           'order' => 6],
        ];
        foreach ($data as $d) {
            $e = new RelationshipType();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->relationshipTypes[$d['code']] = $e;
        }
    }

    private function loadEducationTopics(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'observance',        'label' => 'Observance médicamenteuse', 'order' => 1],
            ['code' => 'hygiene_vie',       'label' => 'Hygiène de vie',            'order' => 2],
            ['code' => 'signes_rejet',      'label' => 'Signes de rejet',           'order' => 3],
            ['code' => 'dietetique',        'label' => 'Diététique',                'order' => 4],
            ['code' => 'activite_physique', 'label' => 'Activité physique',         'order' => 5],
            ['code' => 'gestion_stress',    'label' => 'Gestion du stress',         'order' => 6],
            ['code' => 'autre',             'label' => 'Autre',                     'order' => 7],
        ];
        foreach ($data as $d) {
            $e = new EducationTopic();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->educationTopics[$d['code']] = $e;
        }
    }

    private function loadMedicalHistoryTypes(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'medical',      'label' => 'Médical',      'order' => 1],
            ['code' => 'chirurgical',   'label' => 'Chirurgical',  'order' => 2],
            ['code' => 'familial',      'label' => 'Familial',     'order' => 3],
            ['code' => 'allergique',    'label' => 'Allergique',   'order' => 4],
            ['code' => 'autre',         'label' => 'Autre',        'order' => 5],
        ];
        foreach ($data as $d) {
            $e = new MedicalHistoryType();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->medicalHistoryTypes[$d['code']] = $e;
        }
    }

    private function loadImmunologicalRisks(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'non_immunise',      'label' => 'Non immunisé',        'color' => 'risk-green',  'order' => 1],
            ['code' => 'immunise_sans_dsa', 'label' => 'Immunisé sans DSA',   'color' => 'risk-orange', 'order' => 2],
            ['code' => 'immunise_dsa',      'label' => 'Immunisé DSA',        'color' => 'risk-red',    'order' => 3],
            ['code' => 'abo_incompatible',  'label' => 'ABO incompatible',    'color' => 'risk-red',    'order' => 4],
        ];
        foreach ($data as $d) {
            $e = new ImmunologicalRisk();
            $e->setCode($d['code'])->setLabel($d['label'])->setColorClass($d['color'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->immunologicalRisks[$d['code']] = $e;
        }
    }

    private function loadImmunosuppressiveDrugs(ObjectManager $manager): void
    {
        $data = [
            'advagraf', 'prograf', 'neoral', 'rapamune', 'certican',
            'cellcept', 'myfortic', 'imurel', 'methylprednisolone',
            'mabthera', 'ig_iv', 'soliris', 'thymoglobulines',
            'simulect', 'plasmapherese', 'immuno_absorption',
        ];
        $labels = [
            'advagraf' => 'Advagraf', 'prograf' => 'Prograf', 'neoral' => 'Neoral',
            'rapamune' => 'Rapamune', 'certican' => 'Certican', 'cellcept' => 'Cellcept',
            'myfortic' => 'Myfortic', 'imurel' => 'Imurel', 'methylprednisolone' => 'Methylprednisolone',
            'mabthera' => 'Mabthera', 'ig_iv' => 'Ig IV', 'soliris' => 'Soliris',
            'thymoglobulines' => 'Thymoglobulines', 'simulect' => 'Simulect',
            'plasmapherese' => 'Plasmaphérèse', 'immuno_absorption' => 'Immuno absorption',
        ];
        $order = 1;
        foreach ($data as $code) {
            $e = new ImmunosuppressiveDrug();
            $e->setCode($code)->setLabel($labels[$code])->setDisplayOrder($order++);
            $manager->persist($e);
            $this->immunosuppressiveDrugs[$code] = $e;
        }
    }

    private function loadPerfusionLiquids(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'viaspan', 'label' => 'Viaspan', 'order' => 1],
            ['code' => 'celsior', 'label' => 'Celsior', 'order' => 2],
            ['code' => 'igl',     'label' => 'IGL',     'order' => 3],
            ['code' => 'scott',   'label' => 'Scott',   'order' => 4],
        ];
        foreach ($data as $d) {
            $e = new PerfusionLiquid();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->perfusionLiquids[$d['code']] = $e;
        }
    }

    private function loadPeritonealPositions(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'extra_peritoneal', 'label' => 'Extra Péritonéal', 'order' => 1],
            ['code' => 'intra_peritoneal', 'label' => 'Intra Péritonéal', 'order' => 2],
        ];
        foreach ($data as $d) {
            $e = new PeritonealPosition();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->peritonealPositions[$d['code']] = $e;
        }
    }

    private function loadSurgicalApproaches(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'lombotomie',   'label' => 'Lombotomie',   'order' => 1],
            ['code' => 'coelioscopie', 'label' => 'Cœlioscopie',  'order' => 2],
        ];
        foreach ($data as $d) {
            $e = new SurgicalApproach();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->surgicalApproaches[$d['code']] = $e;
        }
    }

    private function loadPatientProgressValues(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'acquis',     'label' => 'Acquis',     'order' => 1],
            ['code' => 'en_cours',   'label' => 'En cours',   'order' => 2],
            ['code' => 'non_acquis', 'label' => 'Non acquis', 'order' => 3],
        ];
        foreach ($data as $d) {
            $e = new PatientProgress();
            $e->setCode($d['code'])->setLabel($d['label'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->patientProgressValues[$d['code']] = $e;
        }
    }

    private function loadHlaLoci(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'A',  'label' => 'HLA-A',  'required' => true,  'order' => 1],
            ['code' => 'B',  'label' => 'HLA-B',  'required' => true,  'order' => 2],
            ['code' => 'Cw', 'label' => 'HLA-Cw', 'required' => false, 'order' => 3],
            ['code' => 'DR', 'label' => 'HLA-DR', 'required' => true,  'order' => 4],
            ['code' => 'DQ', 'label' => 'HLA-DQ', 'required' => true,  'order' => 5],
            ['code' => 'DP', 'label' => 'HLA-DP', 'required' => false, 'order' => 6],
        ];
        foreach ($data as $d) {
            $e = new HlaLocus();
            $e->setCode($d['code'])->setLabel($d['label'])->setIsRequired($d['required'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->hlaLoci[$d['code']] = $e;
        }
    }

    private function loadSerologyMarkers(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'cmv',           'label' => 'CMV',           'required' => true,  'values' => ['+', '-'],           'order' => 1],
            ['code' => 'ebv',           'label' => 'EBV',           'required' => true,  'values' => ['+', '-'],           'order' => 2],
            ['code' => 'hiv',           'label' => 'HIV',           'required' => true,  'values' => ['+', '-'],           'order' => 3],
            ['code' => 'htlv',          'label' => 'HTLV',          'required' => true,  'values' => ['+', '-'],           'order' => 4],
            ['code' => 'syphilis',      'label' => 'Syphilis',      'required' => true,  'values' => ['+', '-'],           'order' => 5],
            ['code' => 'hcv',           'label' => 'HCV',           'required' => true,  'values' => ['+', '-'],           'order' => 6],
            ['code' => 'agHbs',         'label' => 'Ag HBs',        'required' => true,  'values' => ['+', '-'],           'order' => 7],
            ['code' => 'acHbs',         'label' => 'Ac HBs',        'required' => true,  'values' => ['+', '-'],           'order' => 8],
            ['code' => 'acHbc',         'label' => 'Ac HBc',        'required' => true,  'values' => ['+', '-'],           'order' => 9],
            ['code' => 'toxoplasmosis', 'label' => 'Toxoplasmose',  'required' => false, 'values' => ['+', '-', 'ND'],     'order' => 10],
            ['code' => 'arnc',          'label' => 'ARNc',          'required' => false, 'values' => ['+', '-'],           'order' => 11],
            ['code' => 'dnaB',          'label' => 'DNAb',          'required' => false, 'values' => ['+', '-'],           'order' => 12],
        ];
        foreach ($data as $d) {
            $e = new SerologyMarker();
            $e->setCode($d['code'])->setLabel($d['label'])->setIsRequired($d['required'])->setPossibleValues($d['values'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->serologyMarkers[$d['code']] = $e;
        }
    }

    private function loadVirologicalMarkers(ObjectManager $manager): void
    {
        $data = [
            ['code' => 'CMV',           'label' => 'CMV',          'statuses' => ['D-/R-', 'D-/R+', 'D+/R-', 'D+/R+'], 'order' => 1],
            ['code' => 'EBV',           'label' => 'EBV',          'statuses' => ['D-/R-', 'D-/R+', 'D+/R-', 'D+/R+'], 'order' => 2],
            ['code' => 'toxoplasmosis', 'label' => 'Toxoplasmose', 'statuses' => ['R+', 'R-'],                          'order' => 3],
        ];
        foreach ($data as $d) {
            $e = new VirologicalMarker();
            $e->setCode($d['code'])->setLabel($d['label'])->setPossibleStatuses($d['statuses'])->setDisplayOrder($d['order']);
            $manager->persist($e);
            $this->virologicalMarkers[$d['code']] = $e;
        }
    }

    // ===================================================================
    // USERS
    // ===================================================================

    private function loadUsers(ObjectManager $manager): void
    {
        // Super admin user: Gandalf Le Blanc (full system management)
        $superAdmin = new User();
        $superAdmin->setName('Gandalf');
        $superAdmin->setSurname('Le Blanc');
        $superAdmin->setEmail('superadmin@admin.fr');
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN']);
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'password'));
        $superAdmin->setCristalId('CRISTAL-SADM-001');
        $manager->persist($superAdmin);

        // Admin user: Sam Gamegie (technical admin, no patient access, ROLE_USER only creation)
        $admin = new User();
        $admin->setName('Sam');
        $admin->setSurname('Gamegie');
        $admin->setEmail('admin@admin.fr');
        $admin->setRoles(['ROLE_TECH_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setCristalId('CRISTAL-ADMIN-001');
        $manager->persist($admin);

        // Transplant coordinator: Claire Fontaine (manages donors, links to patients)
        $coordinator = new User();
        $coordinator->setName('Claire');
        $coordinator->setSurname('Fontaine');
        $coordinator->setEmail('coordinateur@chu.fr');
        $coordinator->setRoles(['ROLE_TRANSPLANT_COORDINATOR']);
        $coordinator->setPassword($this->passwordHasher->hashPassword($coordinator, 'password'));
        $coordinator->setCristalId('CRISTAL-COORD-001');
        $manager->persist($coordinator);

        // Doctor user: Dr. Sophie Martin (senior doctor, sees only assigned patients)
        $doctorMartin = new User();
        $doctorMartin->setName('Sophie');
        $doctorMartin->setSurname('Martin');
        $doctorMartin->setEmail('admin-medical@chu.fr');
        $doctorMartin->setRoles(['ROLE_DOCTOR']);
        $doctorMartin->setPassword($this->passwordHasher->hashPassword($doctorMartin, 'password'));
        $doctorMartin->setCristalId('CRISTAL-MADM-001');
        $manager->persist($doctorMartin);
        $this->addReference('doctor-martin', $doctorMartin);

        // Doctor user: John Doe (CHU doctor, sees only assigned patients)
        $doctor = new User();
        $doctor->setName('John');
        $doctor->setSurname('Doe');
        $doctor->setEmail('docteur@chu.fr');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $doctor->setPassword($this->passwordHasher->hashPassword($doctor, 'password'));
        $doctor->setCristalId('CRISTAL-DOC-001');
        $manager->persist($doctor);
        $this->addReference('doctor-doe', $doctor);

        // Nurse user: Marie Curie (read-only access - can view but not modify)
        $nurse = new User();
        $nurse->setName('Marie');
        $nurse->setSurname('Curie');
        $nurse->setEmail('infirmiere@chu.fr');
        $nurse->setRoles(['ROLE_NURSE']);
        $nurse->setPassword($this->passwordHasher->hashPassword($nurse, 'password'));
        $nurse->setCristalId('CRISTAL-INF-001');
        $manager->persist($nurse);
        $this->addReference('nurse-curie', $nurse);

        // External city nephrologist: Dr. Lucie Vasseur (sees only assigned patients)
        $externalDoctor = new User();
        $externalDoctor->setName('Lucie');
        $externalDoctor->setSurname('Vasseur');
        $externalDoctor->setEmail('nephrologue@ville.fr');
        $externalDoctor->setRoles(['ROLE_DOCTOR']);
        $externalDoctor->setPassword($this->passwordHasher->hashPassword($externalDoctor, 'password'));
        $externalDoctor->setCristalId('CRISTAL-EXT-001');
        $manager->persist($externalDoctor);
        $this->addReference('external-doctor', $externalDoctor);

        // Disabled user: for testing account deactivation
        $disabled = new User();
        $disabled->setName('Compte');
        $disabled->setSurname('Désactivé');
        $disabled->setEmail('disabled@test.fr');
        $disabled->setRoles(['ROLE_USER']);
        $disabled->setPassword($this->passwordHasher->hashPassword($disabled, 'password'));
        $disabled->setIsActive(false);
        $manager->persist($disabled);

        // Test user: Benjamin Baillard
        $benjamin = new User();
        $benjamin->setName('Benjamin');
        $benjamin->setSurname('Baillard');
        $benjamin->setEmail('baillard.bjm2@orange.fr');
        $benjamin->setRoles(['ROLE_DOCTOR']);
        $benjamin->setPassword($this->passwordHasher->hashPassword($benjamin, 'password'));
        $manager->persist($benjamin);
        $this->addReference('doctor-benjamin', $benjamin);
    }

    // ===================================================================
    // PATIENTS
    // ===================================================================

    private function loadPatients(ObjectManager $manager): void
    {
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
                $patient->setBloodGroup($this->bloodGroups[$data['bloodGroup']]);
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

        // Generate 300 additional Paris patients to test >200 results confirmation
        $parisFirstNames = ['Adrien', 'Alexandre', 'Alice', 'Amélie', 'Antoine', 'Arnaud', 'Arthur', 'Aurélie', 'Baptiste', 'Bastien', 'Béatrice', 'Benjamin', 'Camille', 'Cédric', 'Charlotte', 'Clara', 'Clément', 'Damien', 'Diane', 'Élise', 'Émile', 'Emma', 'Fabien', 'Florian', 'Gabriel', 'Guillaume', 'Hugo', 'Inès', 'Jade', 'Jules', 'Julie', 'Karine', 'Léa', 'Léo', 'Louis', 'Lucas', 'Lucie', 'Manon', 'Margaux', 'Mathilde', 'Maxime', 'Nathan', 'Nina', 'Noah', 'Noémie', 'Océane', 'Paul', 'Raphaël', 'Romain', 'Sarah', 'Simon', 'Théo', 'Thomas', 'Valentin', 'Valentine', 'Victor', 'Zoé', 'Yann', 'Xavier', 'Quentin'];
        $parisLastNames = ['Adam', 'Aubry', 'Barbier', 'Baron', 'Berger', 'Bertrand', 'Blanchard', 'Boucher', 'Brun', 'Carpentier', 'Chartier', 'Collet', 'Cordier', 'Coulon', 'David', 'Delorme', 'Denis', 'Descamps', 'Dufour', 'Dupuis', 'Etienne', 'Ferry', 'Fleury', 'Garnier', 'Gérard', 'Giraud', 'Grondin', 'Guillot', 'Hardy', 'Hubert', 'Jacob', 'Joly', 'Klein', 'Lacroix', 'Laurent', 'Leclerc', 'Lemoine', 'Leroux', 'Loiseau', 'Louis', 'Marchand', 'Marie', 'Martel', 'Mathieu', 'Menard', 'Monnier', 'Moulin', 'Noel', 'Olivier', 'Paris', 'Pascal', 'Pelletier', 'Pichon', 'Poirier', 'Raymond', 'Regnier', 'Rey', 'Rolland', 'Roussel', 'Roy'];
        $bloodGroupCodes = ['A', 'B', 'AB', 'O'];
        $rhesusValues = ['+', '-'];

        for ($i = 1; $i <= 300; $i++) {
            $sex = $i % 2 === 0 ? 'F' : 'M';
            $firstName = $parisFirstNames[$i % count($parisFirstNames)];
            $lastName = $parisLastNames[$i % count($parisLastNames)];
            $bloodGroupCode = $bloodGroupCodes[$i % count($bloodGroupCodes)];
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
            $patient->setBloodGroup($this->bloodGroups[$bloodGroupCode]);
            $patient->setRhesus($rhesus);

            $manager->persist($patient);
        }
    }

    // ===================================================================
    // PATIENT ASSIGNMENTS
    // ===================================================================

    private function loadPatientAssignments(ObjectManager $manager): void
    {
        /** @var User $externalDoctor */
        $externalDoctor = $this->getReference('external-doctor', User::class);
        /** @var User $doctorDoe */
        $doctorDoe = $this->getReference('doctor-doe', User::class);
        /** @var User $nurseCurie */
        $nurseCurie = $this->getReference('nurse-curie', User::class);
        /** @var User $doctorBenjamin */
        $doctorBenjamin = $this->getReference('doctor-benjamin', User::class);
        /** @var User $doctorMartin */
        $doctorMartin = $this->getReference('doctor-martin', User::class);

        // External nephrologist: patients 2024-001 through 2024-005
        $externalPatients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-001', '2024-002', '2024-003', '2024-004', '2024-005']]
        );
        foreach ($externalPatients as $p) {
            $p->addAuthorizedPractitioner($externalDoctor);
        }

        // Dr. Doe: patients 2024-001 through 2024-015 (including 2024-008, 2024-009)
        $doePatients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-001', '2024-002', '2024-003', '2024-004', '2024-005',
                              '2024-006', '2024-007', '2024-008', '2024-009', '2024-010',
                              '2024-011', '2024-012', '2024-013', '2024-014', '2024-015']]
        );
        foreach ($doePatients as $p) {
            $p->addAuthorizedPractitioner($doctorDoe);
        }

        // Benjamin: patients 2024-016 through 2024-030
        $benjaminPatients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-016', '2024-017', '2024-018', '2024-019', '2024-020',
                              '2024-021', '2024-022', '2024-023', '2024-024', '2024-025',
                              '2024-026', '2024-027', '2024-028', '2024-029', '2024-030']]
        );
        foreach ($benjaminPatients as $p) {
            $p->addAuthorizedPractitioner($doctorBenjamin);
        }

        // Nurse Curie: same patients as Dr. Doe (read-only)
        foreach ($doePatients as $p) {
            $p->addAuthorizedPractitioner($nurseCurie);
        }

        // Dr. Martin: patients 2024-031 through 2024-050 (senior doctor)
        $martinPatients = $manager->getRepository(Patient::class)->findBy(
            ['fileNumber' => ['2024-031', '2024-032', '2024-033', '2024-034', '2024-035',
                              '2024-036', '2024-037', '2024-038', '2024-039', '2024-040',
                              '2024-041', '2024-042', '2024-043', '2024-044', '2024-045',
                              '2024-046', '2024-047', '2024-048', '2024-049', '2024-050']]
        );
        foreach ($martinPatients as $p) {
            $p->addAuthorizedPractitioner($doctorMartin);
        }

        $manager->flush();
    }

    // ===================================================================
    // MEDICAL DATA
    // ===================================================================

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
            ['type' => 'medical', 'description' => 'Hypertension artérielle traitée par inhibiteurs calciques', 'diagnosisDate' => '2015-06-10', 'comment' => 'Bien contrôlée sous traitement'],
            ['type' => 'medical', 'description' => 'Insuffisance rénale chronique stade 5, étiologie glomérulonéphrite', 'diagnosisDate' => '2018-03-20'],
            ['type' => 'chirurgical', 'description' => 'Pose de fistule artério-veineuse au bras gauche', 'diagnosisDate' => '2019-01-15'],
            ['type' => 'allergique', 'description' => 'Allergie documentée à la pénicilline (éruption cutanée)', 'diagnosisDate' => '2010-09-05', 'comment' => 'Utiliser macrolides en alternative'],
            ['type' => 'familial', 'description' => 'Père décédé d\'insuffisance rénale à 62 ans, mère diabétique type 2'],
        ];

        foreach ($entries as $data) {
            $history = new MedicalHistory();
            $history->setPatient($patient);
            $history->setType($this->medicalHistoryTypes[$data['type']]);
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
            ['date' => '2025-01-15', 'practitionerName' => 'Dr. Martin Sophie', 'type' => 'suivi_post_greffe', 'observations' => 'Bonne évolution clinique. Créatinine stable. Pas de signe de rejet.', 'treatmentNotes' => 'Maintien du tacrolimus à 5mg/j', 'nextAppointmentDate' => '2025-02-15'],
            ['date' => '2025-02-15', 'practitionerName' => 'Dr. Martin Sophie', 'type' => 'suivi_post_greffe', 'observations' => 'Patient en bon état général. Tension artérielle bien contrôlée 130/80. Greffon fonctionnel.', 'nextAppointmentDate' => '2025-03-15'],
            ['date' => '2024-12-01', 'practitionerName' => 'Dr. Doe John', 'type' => 'controle', 'observations' => 'Contrôle annuel. Bilan complet demandé. Échographie du greffon sans anomalie.'],
            ['date' => '2024-10-20', 'practitionerName' => 'Dr. Vasseur Lucie', 'type' => 'suivi_post_greffe', 'observations' => 'Suivi néphrologue de ville. Fonction rénale stable. Poursuite du traitement immunosuppresseur.', 'treatmentNotes' => 'Réduction progressive des corticoïdes'],
        ];

        foreach ($entries as $data) {
            $consultation = new Consultation();
            $consultation->setPatient($patient);
            $consultation->setDate(new \DateTime($data['date']));
            $consultation->setPractitionerName($data['practitionerName']);
            $consultation->setType($this->consultationTypes[$data['type']]);
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
            ['sessionDate' => '2025-01-20', 'topic' => 'observance', 'educator' => 'Mme Curie Marie', 'objectives' => 'Comprendre l\'importance de la prise régulière des immunosuppresseurs', 'observations' => 'Patient attentif, bonne compréhension', 'patientProgress' => 'acquis', 'nextSessionDate' => '2025-02-20'],
            ['sessionDate' => '2025-02-20', 'topic' => 'signes_rejet', 'educator' => 'Mme Curie Marie', 'objectives' => 'Savoir identifier les signes précoces de rejet du greffon', 'observations' => 'Patient capable de citer les principaux signes d\'alerte', 'patientProgress' => 'en_cours', 'nextSessionDate' => '2025-03-20'],
            ['sessionDate' => '2024-12-10', 'topic' => 'hygiene_vie', 'educator' => 'M. Laurent Pierre', 'objectives' => 'Adapter l\'alimentation et l\'activité physique post-greffe', 'observations' => 'Conseils diététiques donnés, programme d\'activité physique établi', 'patientProgress' => 'en_cours'],
            ['sessionDate' => '2024-11-05', 'topic' => 'dietetique', 'educator' => 'Mme Blanc Sophie', 'objectives' => 'Régime pauvre en sel et suivi des apports hydriques', 'patientProgress' => 'acquis'],
        ];

        foreach ($entries as $data) {
            $session = new TherapeuticEducation();
            $session->setPatient($patient);
            $session->setSessionDate(new \DateTime($data['sessionDate']));
            $session->setTopic($this->educationTopics[$data['topic']]);
            $session->setEducator($data['educator']);
            if (isset($data['objectives'])) {
                $session->setObjectives($data['objectives']);
            }
            if (isset($data['observations'])) {
                $session->setObservations($data['observations']);
            }
            if (isset($data['patientProgress'])) {
                $session->setPatientProgress($this->patientProgressValues[$data['patientProgress']]);
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

    // ===================================================================
    // DONORS
    // ===================================================================

    private function loadDonors(ObjectManager $manager): void
    {
        $donorsData = [
            // Living donors
            [
                'donorType' => 'living',
                'cristalNumber' => 'CRI-2025-V001',
                'bloodGroup' => 'A',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 42,
                'height' => 165,
                'weight' => 62,
                'lastName' => 'Martin',
                'firstName' => 'Claire',
                'relationshipType' => 'conjoint',
                'creatinine' => '72.00',
                'isotopicClearance' => '95.50',
                'proteinuria' => '0.08',
                'approach' => 'coelioscopie',
                'robot' => true,
                'hla' => ['A' => 2, 'B' => 7, 'Cw' => 4, 'DR' => 11, 'DQ' => 3, 'DP' => 1],
                'serology' => ['cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+'],
                'donorSurgeonName' => 'Dr. Legrand',
                'clampingDate' => '2025-03-10',
                'donorHarvestSide' => 'gauche',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'celsior',
            ],
            [
                'donorType' => 'living',
                'cristalNumber' => 'CRI-2025-V002',
                'bloodGroup' => 'O',
                'rhesus' => '-',
                'sex' => 'M',
                'age' => 38,
                'height' => 178,
                'weight' => 80,
                'lastName' => 'Dupont',
                'firstName' => 'Michel',
                'relationshipType' => 'parent',
                'creatinine' => '85.00',
                'isotopicClearance' => '102.30',
                'proteinuria' => '0.05',
                'approach' => 'lombotomie',
                'robot' => false,
                'hla' => ['A' => 1, 'B' => 8, 'DR' => 15, 'DQ' => 6],
                'serology' => ['cmv' => '-', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '-', 'acHbc' => '-', 'toxoplasmosis' => '-'],
                'donorSurgeonName' => 'Dr. Moreau',
                'clampingDate' => '2025-02-20',
                'donorHarvestSide' => 'droit',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'viaspan',
            ],
            [
                'donorType' => 'living',
                'cristalNumber' => 'CRI-2025-V003',
                'bloodGroup' => 'B',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 55,
                'height' => 160,
                'weight' => 68,
                'lastName' => 'Rousseau',
                'firstName' => 'Anne',
                'relationshipType' => '2eme_degre',
                'creatinine' => '90.00',
                'isotopicClearance' => '88.00',
                'proteinuria' => '0.12',
                'approach' => 'coelioscopie',
                'robot' => true,
                'hla' => ['A' => 3, 'B' => 44, 'Cw' => 5, 'DR' => 4, 'DQ' => 8, 'DP' => 2],
                'serology' => ['cmv' => '+', 'ebv' => '-', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '+', 'toxoplasmosis' => 'ND'],
                'donorSurgeonName' => 'Dr. Petit',
                'clampingDate' => '2025-01-15',
                'donorHarvestSide' => 'gauche',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'igl',
            ],

            // Deceased donors - encephalic
            [
                'donorType' => 'deceased_encephalic',
                'cristalNumber' => 'CRI-2025-D001',
                'bloodGroup' => 'A',
                'rhesus' => '+',
                'sex' => 'M',
                'age' => 52,
                'height' => 175,
                'weight' => 78,
                'originCity' => 'Lyon',
                'deathCause' => 'avc_hemorragique',
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
                'conservationLiquid' => 'celsior',
                'hla' => ['A' => 2, 'B' => 35, 'Cw' => 7, 'DR' => 1, 'DQ' => 5],
                'serology' => ['cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+'],
                'donorSurgeonName' => 'Dr. Bernard',
                'clampingDate' => '2025-03-05',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '1 artère principale',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'celsior',
                'aortaAtheroma' => true,
                'calcifiedAortaPlaques' => false,
                'renalArteryAtheroma' => false,
            ],
            [
                'donorType' => 'deceased_encephalic',
                'cristalNumber' => 'CRI-2025-D002',
                'bloodGroup' => 'O',
                'rhesus' => '-',
                'sex' => 'F',
                'age' => 45,
                'height' => 162,
                'weight' => 58,
                'originCity' => 'Marseille',
                'deathCause' => 'anoxie',
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
                'conservationLiquid' => 'viaspan',
                'hla' => ['A' => 11, 'B' => 27, 'DR' => 7, 'DQ' => 2],
                'serology' => ['cmv' => '-', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '-', 'acHbc' => '-', 'toxoplasmosis' => '-'],
                'donorSurgeonName' => 'Dr. Girard',
                'clampingDate' => '2025-02-28',
                'donorHarvestSide' => 'gauche',
                'mainArtery' => '1 artère principale',
                'upperPolarArtery' => '1 polaire supérieure',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'viaspan',
            ],
            [
                'donorType' => 'deceased_encephalic',
                'cristalNumber' => 'CRI-2025-D003',
                'bloodGroup' => 'AB',
                'rhesus' => '+',
                'sex' => 'M',
                'age' => 63,
                'height' => 180,
                'weight' => 92,
                'originCity' => 'Bordeaux',
                'deathCause' => 'avc_ischemique',
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
                'conservationLiquid' => 'igl',
                'hla' => ['A' => 24, 'B' => 51, 'Cw' => 1, 'DR' => 13, 'DQ' => 6, 'DP' => 4],
                'serology' => ['cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '+', 'toxoplasmosis' => '+'],
                'donorSurgeonName' => 'Dr. Duval',
                'clampingDate' => '2025-01-22',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '2 artères',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'igl',
                'aortaAtheroma' => true,
                'calcifiedAortaPlaques' => true,
                'ostiumArteryAtheroma' => true,
                'renalArteryAtheroma' => false,
            ],

            // Deceased donor - cardiac arrest
            [
                'donorType' => 'deceased_cardiac_arrest',
                'cristalNumber' => 'CRI-2025-C001',
                'bloodGroup' => 'B',
                'rhesus' => '-',
                'sex' => 'M',
                'age' => 48,
                'height' => 172,
                'weight' => 75,
                'originCity' => 'Toulouse',
                'deathCause' => 'avp',
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
                'conservationLiquid' => 'scott',
                'hla' => ['A' => 29, 'B' => 13, 'Cw' => 6, 'DR' => 17, 'DQ' => 9],
                'serology' => ['cmv' => '-', 'ebv' => '-', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-'],
                'donorSurgeonName' => 'Dr. Lambert',
                'clampingDate' => '2025-03-01',
                'donorHarvestSide' => 'gauche',
                'mainArtery' => '1 artère rénale',
                'vein' => '1 veine rénale',
                'veinComment' => 'Veine courte, anastomose sur VCI',
                'perfusionMachine' => 'Oui',
                'perfusionLiquid' => 'scott',
                'digestiveWound' => true,
            ],
            [
                'donorType' => 'deceased_cardiac_arrest',
                'cristalNumber' => 'CRI-2025-C002',
                'bloodGroup' => 'O',
                'rhesus' => '+',
                'sex' => 'F',
                'age' => 35,
                'height' => 168,
                'weight' => 65,
                'originCity' => 'Nantes',
                'deathCause' => 'tc_non_avp',
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
                'conservationLiquid' => 'celsior',
                'hla' => ['A' => 32, 'B' => 44, 'Cw' => 5, 'DR' => 11, 'DQ' => 7, 'DP' => 2],
                'serology' => ['cmv' => '+', 'ebv' => '+', 'hiv' => '-', 'htlv' => '-', 'syphilis' => '-', 'hcv' => '-', 'agHbs' => '-', 'acHbs' => '+', 'acHbc' => '-', 'toxoplasmosis' => '+'],
                'donorSurgeonName' => 'Dr. Faure',
                'clampingDate' => '2025-02-14',
                'donorHarvestSide' => 'droit',
                'mainArtery' => '1 artère rénale',
                'vein' => '1 veine rénale',
                'perfusionMachine' => 'Non',
                'perfusionLiquid' => 'celsior',
            ],
        ];

        foreach ($donorsData as $data) {
            $donor = new Donor();
            $donor->setDonorType($this->donorTypes[$data['donorType']]);
            $donor->setCristalNumber($data['cristalNumber']);
            $donor->setBloodGroup($this->bloodGroups[$data['bloodGroup']]);
            $donor->setRhesus($data['rhesus']);
            $donor->setSex($data['sex']);
            $donor->setAge($data['age']);
            if (isset($data['height'])) { $donor->setHeight($data['height']); }
            if (isset($data['weight'])) { $donor->setWeight($data['weight']); }

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
            if (isset($data['perfusionLiquid'])) { $donor->setPerfusionLiquid($this->perfusionLiquids[$data['perfusionLiquid']]); }

            // Living donor specific
            if (isset($data['lastName'])) { $donor->setLastName($data['lastName']); }
            if (isset($data['firstName'])) { $donor->setFirstName($data['firstName']); }
            if (isset($data['relationshipType'])) { $donor->setRelationshipType($this->relationshipTypes[$data['relationshipType']]); }
            if (isset($data['creatinine'])) { $donor->setCreatinine($data['creatinine']); }
            if (isset($data['isotopicClearance'])) { $donor->setIsotopicClearance($data['isotopicClearance']); }
            if (isset($data['proteinuria'])) { $donor->setProteinuria($data['proteinuria']); }
            if (isset($data['approach'])) { $donor->setApproach($this->surgicalApproaches[$data['approach']]); }
            if (isset($data['robot'])) { $donor->setRobot($data['robot']); }

            // Deceased donor specific
            if (isset($data['originCity'])) { $donor->setOriginCity($data['originCity']); }
            if (isset($data['deathCause'])) { $donor->setDeathCause($this->deathCauses[$data['deathCause']]); }
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
            if (isset($data['conservationLiquid'])) { $donor->setConservationLiquid($this->perfusionLiquids[$data['conservationLiquid']]); }

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

            // HLA typings (junction table)
            if (isset($data['hla'])) {
                foreach ($data['hla'] as $locusCode => $value) {
                    if (isset($this->hlaLoci[$locusCode])) {
                        $typing = new DonorHlaTyping();
                        $typing->setDonor($donor);
                        $typing->setHlaLocus($this->hlaLoci[$locusCode]);
                        $typing->setValue($value);
                        $manager->persist($typing);
                    }
                }
            }

            // Serology results (junction table)
            if (isset($data['serology'])) {
                foreach ($data['serology'] as $markerCode => $result) {
                    if (isset($this->serologyMarkers[$markerCode])) {
                        $serology = new DonorSerology();
                        $serology->setDonor($donor);
                        $serology->setSerologyMarker($this->serologyMarkers[$markerCode]);
                        $serology->setResult($result);
                        $manager->persist($serology);
                    }
                }
            }
        }
    }

    // ===================================================================
    // TRANSPLANTS
    // ===================================================================

    /**
     * Map from fixture drug labels to reference entity codes.
     */
    private function getDrugCode(string $label): string
    {
        $map = [
            'Advagraf' => 'advagraf', 'Prograf' => 'prograf', 'Neoral' => 'neoral',
            'Rapamune' => 'rapamune', 'Certican' => 'certican', 'Cellcept' => 'cellcept',
            'Myfortic' => 'myfortic', 'Imurel' => 'imurel', 'Methylprednisolone' => 'methylprednisolone',
            'Mabthera' => 'mabthera', 'Ig IV' => 'ig_iv', 'Soliris' => 'soliris',
            'Thymoglobulines' => 'thymoglobulines', 'Simulect' => 'simulect',
            'Plasmaphérèse' => 'plasmapherese', 'Plasmaphérese' => 'plasmapherese',
            'Immuno absorption' => 'immuno_absorption',
        ];

        return $map[$label] ?? strtolower($label);
    }

    /**
     * Map from fixture immunological risk labels to reference entity codes.
     */
    private function getImmunologicalRiskCode(string $label): string
    {
        $map = [
            'Non immunisé' => 'non_immunise',
            'Immunisé sans DSA' => 'immunise_sans_dsa',
            'Immunisé DSA' => 'immunise_dsa',
            'ABO incompatible' => 'abo_incompatible',
        ];

        return $map[$label] ?? $label;
    }

    /**
     * Map from fixture transplant type labels to reference entity codes.
     */
    private function getTransplantTypeCode(string $label): string
    {
        $map = [
            'Rein' => 'rein',
            'Rein donneur vivant' => 'rein_donneur_vivant',
            'Rein-pancréas' => 'rein_pancreas',
            'Rein-foie' => 'rein_foie',
            'Rein-cœur' => 'rein_coeur',
            'Rein-coeur' => 'rein_coeur',
            'Autre' => 'autre',
        ];

        return $map[$label] ?? strtolower($label);
    }

    /**
     * Map from fixture peritoneal position labels to reference entity codes.
     */
    private function getPeritonealPositionCode(string $label): string
    {
        $map = [
            'Extra Péritonéal' => 'extra_peritoneal',
            'Intra Péritonéal' => 'intra_peritoneal',
        ];

        return $map[$label] ?? $label;
    }

    private function loadTransplants(ObjectManager $manager): void
    {
        $patientRepo = $manager->getRepository(Patient::class);
        $donorRepo = $manager->getRepository(Donor::class);

        // Map CRISTAL numbers to Donor entities
        $donors = [];
        foreach (['CRI-2025-V001', 'CRI-2025-V002', 'CRI-2025-V003', 'CRI-2025-D001', 'CRI-2025-D002', 'CRI-2025-D003', 'CRI-2025-C001', 'CRI-2025-C002'] as $cristal) {
            $donor = $donorRepo->findOneBy(['cristalNumber' => $cristal]);
            if ($donor) {
                $donors[$cristal] = $donor;
            }
        }

        $transplantsData = [
            // Patient 2024-001 - Living donor transplant, functional
            [
                'patientFileNumber' => '2024-001',
                'transplantDate' => '2024-06-15',
                'rank' => 1,
                'donorType' => 'living',
                'transplantType' => 'Rein donneur vivant',
                'isGraftFunctional' => true,
                'surgeonName' => 'Pr. Legrand François',
                'declampingDate' => '2024-06-15',
                'declampingTime' => '10:45',
                'harvestSide' => 'gauche',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 180,
                'anastomosisDuration' => 35,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R+',
                'ebvStatus' => 'D+/R+',
                'toxoplasmosisStatus' => 'R+',
                'hla' => ['A' => 1, 'B' => 1, 'DR' => 0, 'DQ' => 1],
                'immunologicalRisk' => 'Non immunisé',
                'immunosuppressiveConditioning' => ['Advagraf', 'Cellcept', 'Methylprednisolone'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-06-14',
                'hasProtocol' => false,
                'cristalNumber' => 'CRI-2025-V001',
                'comment' => 'Greffe de donneur vivant conjoint. Suites opératoires simples. Reprise de fonction immédiate.',
            ],
            // Patient 2024-002 - Deceased encephalic donor, functional
            [
                'patientFileNumber' => '2024-002',
                'transplantDate' => '2024-09-22',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein',
                'isGraftFunctional' => true,
                'surgeonName' => 'Dr. Bernard Michel',
                'declampingDate' => '2024-09-22',
                'declampingTime' => '14:30',
                'harvestSide' => 'droit',
                'transplantSide' => 'gauche',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 720,
                'anastomosisDuration' => 42,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R-',
                'ebvStatus' => 'D+/R+',
                'toxoplasmosisStatus' => 'R-',
                'hla' => ['A' => 2, 'B' => 1, 'Cw' => 1, 'DR' => 1, 'DQ' => 0],
                'immunologicalRisk' => 'Non immunisé',
                'immunosuppressiveConditioning' => ['Prograf', 'Myfortic', 'Methylprednisolone', 'Simulect'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-09-21',
                'hasProtocol' => true,
                'cristalNumber' => 'CRI-2025-D001',
                'comment' => 'Ischémie froide longue (12h). Retard de reprise de fonction, dialyse transitoire post-greffe.',
            ],
            // Patient 2024-003 - Deceased encephalic, graft non-functional (lost)
            [
                'patientFileNumber' => '2024-003',
                'transplantDate' => '2023-03-10',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein',
                'isGraftFunctional' => false,
                'graftEndDate' => '2024-11-05',
                'graftEndCause' => 'Rejet chronique humoral avec DSA anti-HLA de novo. Retour en dialyse.',
                'surgeonName' => 'Dr. Girard Anne',
                'declampingDate' => '2023-03-10',
                'declampingTime' => '16:10',
                'harvestSide' => 'gauche',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 840,
                'anastomosisDuration' => 50,
                'jjProbe' => false,
                'cmvStatus' => 'D-/R+',
                'ebvStatus' => 'D-/R-',
                'hla' => ['A' => 2, 'B' => 2, 'DR' => 2, 'DQ' => 1],
                'immunologicalRisk' => 'Immunisé DSA',
                'immunosuppressiveConditioning' => ['Prograf', 'Cellcept', 'Methylprednisolone', 'Thymoglobulines'],
                'dialysis' => true,
                'lastDialysisDate' => '2023-03-09',
                'hasProtocol' => false,
                'cristalNumber' => 'CRI-2025-D002',
                'comment' => 'Patient ré-inscrit sur liste d\'attente pour 2ème greffe.',
            ],
            // Patient 2024-003 - 2nd transplant (re-transplant), functional
            [
                'patientFileNumber' => '2024-003',
                'transplantDate' => '2025-01-20',
                'rank' => 2,
                'donorType' => 'living',
                'transplantType' => 'Rein donneur vivant',
                'isGraftFunctional' => true,
                'surgeonName' => 'Pr. Legrand François',
                'declampingDate' => '2025-01-20',
                'declampingTime' => '11:20',
                'harvestSide' => 'gauche',
                'transplantSide' => 'gauche',
                'peritonealPosition' => 'Intra Péritonéal',
                'totalIschemiaMinutes' => 150,
                'anastomosisDuration' => 55,
                'jjProbe' => true,
                'cmvStatus' => 'D-/R+',
                'ebvStatus' => 'D+/R-',
                'toxoplasmosisStatus' => 'R+',
                'hla' => ['A' => 0, 'B' => 1, 'DR' => 1, 'DQ' => 0],
                'immunologicalRisk' => 'Immunisé sans DSA',
                'immunosuppressiveConditioning' => ['Advagraf', 'Cellcept', 'Methylprednisolone', 'Mabthera', 'Ig IV'],
                'dialysis' => true,
                'lastDialysisDate' => '2025-01-19',
                'hasProtocol' => true,
                'cristalNumber' => 'CRI-2025-V002',
                'comment' => 'Re-greffe après perte du premier greffon. Protocole de désensibilisation pré-greffe. Bonne reprise de fonction.',
            ],
            // Patient 2024-004 - Deceased cardiac arrest, functional
            [
                'patientFileNumber' => '2024-004',
                'transplantDate' => '2024-11-08',
                'rank' => 1,
                'donorType' => 'deceased_cardiac_arrest',
                'transplantType' => 'Rein',
                'isGraftFunctional' => true,
                'surgeonName' => 'Dr. Lambert Pierre',
                'declampingDate' => '2024-11-08',
                'declampingTime' => '08:55',
                'harvestSide' => 'gauche',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 540,
                'anastomosisDuration' => 38,
                'jjProbe' => true,
                'cmvStatus' => 'D-/R-',
                'ebvStatus' => 'D-/R+',
                'hla' => ['A' => 1, 'B' => 0, 'Cw' => 0, 'DR' => 1, 'DQ' => 1],
                'immunologicalRisk' => 'Non immunisé',
                'immunosuppressiveConditioning' => ['Prograf', 'Myfortic', 'Methylprednisolone', 'Simulect'],
                'dialysis' => false,
                'hasProtocol' => false,
                'cristalNumber' => 'CRI-2025-C001',
                'comment' => 'Donneur après arrêt cardiaque. Reprise de fonction retardée J5.',
            ],
            // Patient 2024-005 - Rein-pancréas, deceased encephalic, functional
            [
                'patientFileNumber' => '2024-005',
                'transplantDate' => '2024-04-03',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein-pancréas',
                'isGraftFunctional' => true,
                'surgeonName' => 'Pr. Duval Catherine',
                'declampingDate' => '2024-04-03',
                'declampingTime' => '13:00',
                'harvestSide' => 'droit',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Intra Péritonéal',
                'totalIschemiaMinutes' => 660,
                'anastomosisDuration' => 65,
                'jjProbe' => false,
                'cmvStatus' => 'D+/R+',
                'ebvStatus' => 'D+/R-',
                'toxoplasmosisStatus' => 'R-',
                'hla' => ['A' => 1, 'B' => 2, 'DP' => 1, 'DR' => 0, 'DQ' => 2],
                'immunologicalRisk' => 'Immunisé sans DSA',
                'immunosuppressiveConditioning' => ['Prograf', 'Cellcept', 'Methylprednisolone', 'Thymoglobulines'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-04-02',
                'hasProtocol' => true,
                'cristalNumber' => 'CRI-2025-D003',
                'comment' => 'Double greffe rein-pancréas pour néphropathie diabétique. Sevrage insuline à J10.',
            ],
            // Patient 2024-006 - Living donor, ABO incompatible, functional
            [
                'patientFileNumber' => '2024-006',
                'transplantDate' => '2024-08-12',
                'rank' => 1,
                'donorType' => 'living',
                'transplantType' => 'Rein donneur vivant',
                'isGraftFunctional' => true,
                'surgeonName' => 'Dr. Petit Laurent',
                'declampingDate' => '2024-08-12',
                'declampingTime' => '09:30',
                'harvestSide' => 'gauche',
                'transplantSide' => 'gauche',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 120,
                'anastomosisDuration' => 40,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R-',
                'ebvStatus' => 'D-/R+',
                'toxoplasmosisStatus' => 'R+',
                'hla' => ['A' => 0, 'B' => 0, 'DR' => 1, 'DQ' => 0],
                'immunologicalRisk' => 'ABO incompatible',
                'immunosuppressiveConditioning' => ['Advagraf', 'Myfortic', 'Methylprednisolone', 'Mabthera', 'Plasmaphérese', 'Ig IV'],
                'dialysis' => false,
                'hasProtocol' => true,
                'cristalNumber' => 'CRI-2025-V003',
                'comment' => 'Greffe ABO incompatible. Protocole de désensibilisation avec plasmaphérèses. Excellente reprise de fonction immédiate.',
            ],
            // Patient 2024-007 - Deceased cardiac arrest, functional
            [
                'patientFileNumber' => '2024-007',
                'transplantDate' => '2025-02-05',
                'rank' => 1,
                'donorType' => 'deceased_cardiac_arrest',
                'transplantType' => 'Rein',
                'isGraftFunctional' => true,
                'surgeonName' => 'Dr. Faure Nathalie',
                'declampingDate' => '2025-02-05',
                'declampingTime' => '17:45',
                'harvestSide' => 'droit',
                'transplantSide' => 'gauche',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 480,
                'anastomosisDuration' => 32,
                'jjProbe' => false,
                'cmvStatus' => 'D+/R+',
                'ebvStatus' => 'D+/R+',
                'hla' => ['A' => 1, 'B' => 1, 'DR' => 2, 'DQ' => 1],
                'immunologicalRisk' => 'Immunisé sans DSA',
                'immunosuppressiveConditioning' => ['Neoral', 'Cellcept', 'Methylprednisolone', 'Simulect'],
                'dialysis' => true,
                'lastDialysisDate' => '2025-02-04',
                'hasProtocol' => false,
                'cristalNumber' => 'CRI-2025-C002',
            ],
            // Patient 2024-008 — Second kidney from CRI-2025-D001 (same deceased donor as patient 2024-002)
            [
                'patientFileNumber' => '2024-008',
                'transplantDate' => '2024-09-22',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein',
                'isGraftFunctional' => true,
                'surgeonName' => 'Dr. Bernard Michel',
                'declampingDate' => '2024-09-22',
                'declampingTime' => '16:00',
                'harvestSide' => 'gauche',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 780,
                'anastomosisDuration' => 44,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R+',
                'ebvStatus' => 'D+/R+',
                'hla' => ['A' => 2, 'B' => 1, 'Cw' => 1, 'DR' => 1, 'DQ' => 0],
                'immunologicalRisk' => 'Non immunisé',
                'immunosuppressiveConditioning' => ['Prograf', 'Myfortic', 'Methylprednisolone', 'Simulect'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-09-21',
                'hasProtocol' => true,
                'cristalNumber' => 'CRI-2025-D001',
                'comment' => 'Deuxième rein du même donneur (CRI-2025-D001). Rein gauche. Ischémie froide un peu plus longue. Bonne reprise de fonction.',
            ],
            // Patient 2024-009 — Second kidney from CRI-2025-D003 (same deceased donor as patient 2024-005)
            [
                'patientFileNumber' => '2024-009',
                'transplantDate' => '2024-04-03',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein',
                'isGraftFunctional' => true,
                'surgeonName' => 'Pr. Duval Catherine',
                'declampingDate' => '2024-04-03',
                'declampingTime' => '15:30',
                'harvestSide' => 'gauche',
                'transplantSide' => 'gauche',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 720,
                'anastomosisDuration' => 48,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R-',
                'ebvStatus' => 'D+/R+',
                'toxoplasmosisStatus' => 'R+',
                'hla' => ['A' => 1, 'B' => 2, 'DR' => 0, 'DQ' => 2],
                'immunologicalRisk' => 'Immunisé sans DSA',
                'immunosuppressiveConditioning' => ['Prograf', 'Cellcept', 'Methylprednisolone', 'Simulect'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-04-02',
                'hasProtocol' => false,
                'cristalNumber' => 'CRI-2025-D003',
                'comment' => 'Deuxième rein du même donneur (CRI-2025-D003). Rein gauche. Reprise de fonction rapide.',
            ],
            // Patient 2024-010 - Rein-foie, deceased, no donor entity linked
            [
                'patientFileNumber' => '2024-010',
                'transplantDate' => '2024-07-19',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein-foie',
                'isGraftFunctional' => true,
                'surgeonName' => 'Pr. Moreau Jean-Philippe',
                'declampingDate' => '2024-07-19',
                'declampingTime' => '15:20',
                'harvestSide' => 'droit',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Intra Péritonéal',
                'totalIschemiaMinutes' => 600,
                'anastomosisDuration' => 70,
                'jjProbe' => true,
                'cmvStatus' => 'D-/R+',
                'ebvStatus' => 'D+/R+',
                'toxoplasmosisStatus' => 'R-',
                'hla' => ['A' => 2, 'B' => 1, 'Cw' => 2, 'DP' => 0, 'DR' => 1, 'DQ' => 2],
                'immunologicalRisk' => 'Immunisé DSA',
                'immunosuppressiveConditioning' => ['Prograf', 'Cellcept', 'Methylprednisolone', 'Thymoglobulines', 'Soliris'],
                'dialysis' => true,
                'lastDialysisDate' => '2024-07-18',
                'hasProtocol' => true,
                'comment' => 'Double greffe rein-foie pour polykystose hépato-rénale. Intervention longue (8h). Suites complexes mais favorables.',
            ],
            // Patient 2024-015 - Old transplant, graft non-functional
            [
                'patientFileNumber' => '2024-015',
                'transplantDate' => '2018-05-20',
                'rank' => 1,
                'donorType' => 'deceased_encephalic',
                'transplantType' => 'Rein',
                'isGraftFunctional' => false,
                'graftEndDate' => '2023-08-15',
                'graftEndCause' => 'Néphropathie chronique d\'allogreffe. Fibrose interstitielle et atrophie tubulaire sévères.',
                'surgeonName' => 'Dr. Morel Patrick',
                'harvestSide' => 'gauche',
                'transplantSide' => 'droit',
                'peritonealPosition' => 'Extra Péritonéal',
                'totalIschemiaMinutes' => 900,
                'anastomosisDuration' => 45,
                'jjProbe' => true,
                'cmvStatus' => 'D+/R-',
                'hla' => ['A' => 2, 'B' => 2, 'DR' => 1, 'DQ' => 2],
                'immunologicalRisk' => 'Immunisé sans DSA',
                'immunosuppressiveConditioning' => ['Neoral', 'Imurel', 'Methylprednisolone'],
                'dialysis' => true,
                'lastDialysisDate' => '2018-05-19',
                'hasProtocol' => false,
                'comment' => 'Greffe ancienne (2018). Perte progressive du greffon sur 5 ans. Patient en hémodialyse depuis août 2023.',
            ],
        ];

        foreach ($transplantsData as $data) {
            $patient = $patientRepo->findOneBy(['fileNumber' => $data['patientFileNumber']]);
            if (!$patient) {
                continue;
            }

            $transplant = new Transplant();
            $transplant->setPatient($patient);
            $transplant->setTransplantDate(new \DateTime($data['transplantDate']));
            $transplant->setRank($data['rank']);
            $transplant->setDonorType($this->donorTypes[$data['donorType']]);
            $transplant->setTransplantType($this->transplantTypes[$this->getTransplantTypeCode($data['transplantType'])]);
            $transplant->setIsGraftFunctional($data['isGraftFunctional']);

            if (isset($data['graftEndDate'])) {
                $transplant->setGraftEndDate(new \DateTime($data['graftEndDate']));
            }
            if (isset($data['graftEndCause'])) {
                $transplant->setGraftEndCause($data['graftEndCause']);
            }

            $transplant->setSurgeonName($data['surgeonName'] ?? null);

            if (isset($data['declampingDate'])) {
                $transplant->setDeclampingDate(new \DateTime($data['declampingDate']));
            }
            if (isset($data['declampingTime'])) {
                $transplant->setDeclampingTime(new \DateTime($data['declampingTime']));
            }

            $transplant->setHarvestSide($data['harvestSide']);
            $transplant->setTransplantSide($data['transplantSide']);
            $transplant->setPeritonealPosition($this->peritonealPositions[$this->getPeritonealPositionCode($data['peritonealPosition'])]);
            $transplant->setTotalIschemiaMinutes($data['totalIschemiaMinutes']);
            $transplant->setAnastomosisDuration($data['anastomosisDuration']);
            $transplant->setJjProbe($data['jjProbe']);

            if (isset($data['comment'])) {
                $transplant->setComment($data['comment']);
            }

            // Immunological risk (reference entity)
            $transplant->setImmunologicalRisk($this->immunologicalRisks[$this->getImmunologicalRiskCode($data['immunologicalRisk'])]);

            // Immunosuppressive drugs (ManyToMany)
            foreach ($data['immunosuppressiveConditioning'] as $drugLabel) {
                $drugCode = $this->getDrugCode($drugLabel);
                if (isset($this->immunosuppressiveDrugs[$drugCode])) {
                    $transplant->addImmunosuppressiveDrug($this->immunosuppressiveDrugs[$drugCode]);
                }
            }

            // Dialysis
            $transplant->setDialysis($data['dialysis']);
            if (isset($data['lastDialysisDate'])) {
                $transplant->setLastDialysisDate(new \DateTime($data['lastDialysisDate']));
            }

            // Protocol
            $transplant->setHasProtocol($data['hasProtocol']);

            // Link to Donor entity if CRISTAL number provided
            if (isset($data['cristalNumber']) && isset($donors[$data['cristalNumber']])) {
                $transplant->setDonor($donors[$data['cristalNumber']]);
            }

            $manager->persist($transplant);

            // HLA incompatibilities (junction table)
            if (isset($data['hla'])) {
                foreach ($data['hla'] as $locusCode => $count) {
                    if (isset($this->hlaLoci[$locusCode])) {
                        $incompat = new TransplantHlaIncompatibility();
                        $incompat->setTransplant($transplant);
                        $incompat->setHlaLocus($this->hlaLoci[$locusCode]);
                        $incompat->setIncompatibilityCount($count);
                        $manager->persist($incompat);
                    }
                }
            }

            // Virological statuses (junction table)
            $virologicalMap = [
                'cmvStatus' => 'CMV',
                'ebvStatus' => 'EBV',
                'toxoplasmosisStatus' => 'toxoplasmosis',
            ];
            foreach ($virologicalMap as $dataKey => $markerCode) {
                if (isset($data[$dataKey]) && isset($this->virologicalMarkers[$markerCode])) {
                    $viroStatus = new TransplantVirologicalStatus();
                    $viroStatus->setTransplant($transplant);
                    $viroStatus->setVirologicalMarker($this->virologicalMarkers[$markerCode]);
                    $viroStatus->setStatus($data[$dataKey]);
                    $manager->persist($viroStatus);
                }
            }
        }
    }
}
