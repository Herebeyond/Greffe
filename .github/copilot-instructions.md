# Copilot Instructions - Kidney Transplant Management Platform

## Project Overview

> **⚠️ EDUCATIONAL PROJECT DISCLAIMER**
> 
> This application is a **BTS SIO (Services Informatiques aux Organisations) student exercise project**. It is NOT a real hospital application and is NOT intended for actual medical use.
>
> **Limitations due to educational context:**
> - **CRISTAL Integration**: Cannot connect to the real French national organ allocation system. A mock/simulated CRISTAL code system is implemented instead.
> - **CPS Authentication**: Real Carte Professionnel de Santé authentication is not available. Standard form-based authentication is used.
> - **Medical Data**: All patient data is fictional and for demonstration purposes only.
> - **Regulatory Compliance**: While the application follows security best practices, it has not undergone official medical software certification.

This application is a **medical management platform for kidney transplants** (Greffes Rénales). It is designed to assist healthcare professionals (doctors, surgeons, nurses, administrators) in managing:

- **Patients**: Recipients awaiting or having received a kidney transplant
- **Donors**: Living donors (Donneur vivant) and deceased donors (Donneur décédé)
- **Transplants (Greffes)**: Surgical interventions linking a donor to a patient recipient
- **Medical Reference Data**: HLA typing, immunological risks, immunosuppressive protocols, virological status, etc.

The platform will integrate with the French national organ allocation system (CRISTAL) and follow strict medical data protection regulations.

---

## Current Project Status

**This is a NEW project** based on the [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker) template. The application is currently a fresh Symfony skeleton and will be built incrementally.

### Current Structure

```
├── bin/                    # Symfony console
├── config/                 # Configuration files
│   ├── packages/           # Bundle configurations
│   ├── routes/             # Route definitions
│   ├── bundles.php         # Registered bundles
│   ├── routes.yaml         # Main routing
│   └── services.yaml       # Service definitions
├── frankenphp/             # FrankenPHP configuration
├── public/                 # Web root
├── src/                    # Application source code
│   ├── Command/            # Console commands (encryption key generation)
│   ├── Controller/         # Controllers (Home, Login, Patient, Consultation,
│   │                       #   BiologicalResult, MedicalHistory, TherapeuticEducation,
│   │                       #   Transplant, Donor, Admin, Password, Profile, BreakTheGlass)
│   ├── DataFixtures/       # Doctrine fixtures for test data
│   ├── Doctrine/           # Custom Doctrine types (encrypted_string)
│   ├── Entity/             # Doctrine entities (User, Patient, Consultation,
│   │                       #   BiologicalResult, MedicalHistory, TherapeuticEducation,
│   │                       #   Transplant, Donor, AuditLog, LoginActivity, PasswordHistory,
│   │                       #   BreakTheGlassAccess)
│   ├── EventSubscriber/    # AuditLogSubscriber (route-based audit logging)
│   ├── Form/               # Form types (Patient, Consultation, BiologicalResult,
│   │                       #   MedicalHistory, TherapeuticEducation, Transplant,
│   │                       #   DonorData, Donor, User, Password, BreakTheGlass, etc.)
│   ├── Repository/         # Doctrine repositories
│   ├── Security/           # Voters (PatientAccessVoter, WriteAccessVoter),
│   │                       #   LoginActivityListener
│   ├── Service/            # EncryptionService
│   └── Kernel.php          # Application kernel
├── templates/              # Twig templates organized by feature
├── translations/           # Translation files
├── migrations/             # Doctrine migrations
├── var/                    # Cache and logs
├── vendor/                 # Dependencies
├── compose.yaml            # Docker Compose configuration
├── compose.override.yaml   # Development overrides
├── compose.prod.yaml       # Production configuration
└── Dockerfile              # Container build instructions
```

---

## Keeping This File Up-to-Date

**IMPORTANT**: This `copilot-instructions.md` file must be kept synchronized with the project.

When making changes to the project, **update this file** if the change affects:

- **Technical stack**: New packages, version upgrades, removed dependencies
- **Project structure**: New directories, renamed files, architectural changes
- **Conventions**: New coding standards, naming conventions, patterns
- **Features**: New major features, removed functionality
- **Roles/Security**: Changes to user roles or access control
- **Configuration**: New environment variables, config files

This ensures Copilot always has accurate context about the project's current state.

---

## Technical Stack

### Currently Installed

- **Framework**: Symfony 8.0
- **PHP Version**: 8.4+
- **Database**: PostgreSQL 16 (via Doctrine ORM)
- **Containerization**: Docker with FrankenPHP
- **Authentication**: Symfony Security bundle with form login
- **Encryption**: paragonie/halite (libsodium-based field-level encryption)

#### Symfony Packages
  - `symfony/framework-bundle` - Core framework
  - `symfony/twig-bundle` - Templating engine
  - `symfony/security-bundle` - Authentication & authorization
  - `symfony/validator` - Form and data validation
  - `symfony/translation` - Internationalization
  - `symfony/property-info` - Property metadata
  - `symfony/cache` - Caching system

#### Doctrine Packages (ORM & Database)
  - `doctrine/orm` (3.6.2) - Object-Relational Mapper
  - `doctrine/dbal` (4.4.2) - Database Abstraction Layer
  - `doctrine/doctrine-bundle` (3.2.2) - Symfony Doctrine integration
  - `doctrine/doctrine-migrations-bundle` (4.0.0) - Database migrations
  - `doctrine/migrations` (3.9.6) - Migration engine
  - `doctrine/collections` (2.6.0) - Collection utilities
  - `doctrine/event-manager` (2.1.1) - Event handling
  - `doctrine/inflector` (2.1.0) - String manipulation
  - `doctrine/instantiator` (2.1.0) - Object instantiation
  - `doctrine/lexer` (3.0.1) - Lexical parser
  - `doctrine/persistence` (4.1.1) - Persistence abstraction
  - `doctrine/sql-formatter` (1.5.4) - SQL formatting

#### Encryption Packages (Halite/Libsodium)
  - `paragonie/halite` (5.1.4) - High-level cryptography interface
  - `paragonie/hidden-string` (2.2.0) - Secure string encapsulation
  - `paragonie/constant_time_encoding` (3.1.3) - Timing-safe encoding
  - `paragonie/sodium_compat` (2.5.0) - Pure PHP libsodium fallback

#### Templating Packages
  - `twig/twig` (3.23.0) - Template engine
  - `twig/extra-bundle` (3.23.0) - Extra Twig extensions

#### PSR Standards
  - `psr/cache` (3.0.0) - Caching interface
  - `psr/clock` (1.0.0) - Clock interface
  - `psr/container` (2.0.2) - Container interface
  - `psr/event-dispatcher` (1.0.0) - Event dispatcher interface
  - `psr/log` (3.0.2) - Logging interface

### Planned (To Be Installed)

- **Frontend**: Bootstrap, Stimulus/Turbo
- **API**: API Platform
- **Admin**: EasyAdmin bundle

---

## Password Management

### Password History

The `password_history` table stores previous password hashes to prevent reuse. When a password is changed (by admin action or self-change), the old hashed password is recorded with a timestamp and reason.

- **Reuse prevention**: Checks current password + last 5 passwords in history
- **Change tracking**: Records who initiated the change (`admin_change`, `self_change`)
- **Change date**: `passwordChangedAt` on User entity tracks the last change date (for future forced-change policy)

### Forgot Password

There is **no self-service password reset flow**. If a user forgets their password, the login page displays contact information for tech administrators. Admins can reset passwords via the admin panel.

---

## Audit Logging

The application implements a comprehensive audit trail system that logs all user actions.

### How It Works

- An `AuditLogSubscriber` listens to Symfony kernel controller events
- Every significant action (view, create, edit, delete, search, password change) is logged to the `audit_log` table
- Login/logout events are tracked separately via the existing `LoginActivity` entity

### Logged Information

| Field | Description |
|-------|-------------|
| `user` / `userIdentifier` | Who performed the action |
| `action` | Type: view, create, edit, delete, search, password_change |
| `entityType` | Which entity: Patient, Consultation, BiologicalResult, etc. |
| `entityId` | ID of the affected record |
| `routeName` | Symfony route name |
| `httpMethod` | GET or POST |
| `uri` | Request path |
| `ipAddress` | Client IP |
| `details` | Additional context (search criteria, patient ID) |
| `createdAt` | Timestamp |

### Admin Viewer

- **Route**: `/admin/logs` (ROLE_TECH_ADMIN only)
- **Filters**: by user, action type, entity type, date range
- **Navigation**: "Journal d'audit" link in header (red admin button)

---

## Docker Commands

The application runs in Docker containers. Common commands:

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Run Symfony console commands
docker compose exec php bin/console <command>

# Install Composer packages
docker compose exec php composer require <package>

# Access PHP container shell
docker compose exec php sh

# Generate encryption key (required before first use)
docker compose exec php bin/console app:generate-encryption-key
```

> **⚠️ IMPORTANT**: After every code change (controller, entity, template, config, etc.), **always clear the Symfony cache**:
> ```bash
> docker compose exec php php bin/console cache:clear
> ```
> This ensures the application reflects the latest changes immediately. Failing to clear the cache can lead to stale behavior or misleading errors.

---

## Data Encryption

Sensitive medical data is encrypted at the field level using **paragonie/halite** (libsodium).

### Encrypted Fields

Use the `encrypted_string` Doctrine type for any sensitive PII or medical data:

```php
#[ORM\Column(type: 'encrypted_string')]
private ?string $sensitiveField = null;
```

### Key Management

- Encryption key stored at: `var/encryption.key` (default)
- **CRITICAL**: Back up this key securely - if lost, encrypted data cannot be recovered
- In production, set `ENCRYPTION_KEY_PATH` environment variable to store key outside the app directory
- Key file is automatically added to `.gitignore`

### Initial Setup

```bash
# Generate encryption key (run once per environment)
docker compose exec php bin/console app:generate-encryption-key
```

---

## Database Backups

The application uses **incremental backups** with PostgreSQL WAL (Write-Ahead Logging) archiving.

### Backup Types

- **Full Backup**: Daily at 2:00 AM using `pg_basebackup`
- **Incremental Backup**: Hourly WAL file synchronization

### Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `BACKUP_RETENTION_DAYS` | 7 | Days to keep full backups |
| `WAL_RETENTION_DAYS` | 3 | Days to keep WAL files |
| `INCREMENTAL_INTERVAL` | 3600 | Seconds between incremental backups |

### Commands

```bash
# View backup logs
docker compose logs -f backup

# Manual full backup
docker compose exec backup /scripts/full-backup.sh

# Manual incremental backup
docker compose exec backup /scripts/incremental-backup.sh

# List available backups
docker compose exec backup ls -la /backups/full/

# Restore (interactive)
docker compose exec backup /scripts/restore.sh <backup_name>
```

See `docs/BACKUP.md` for full documentation.

---

## Symfony Conventions

This application uses Symfony as its root framework. Follow these guidelines:

- **ALWAYS prioritize Symfony console commands over writing files directly** when applicable. Use `make:controller`, `make:entity`, `make:form`, `make:migration`, etc. instead of manually creating files.

- Place files in the appropriate directories according to Symfony structure:
  - Controllers in `src/Controller/`
  - Entities in `src/Entity/` (create when needed)
  - Forms in `src/Form/` (create when needed)
  - Repositories in `src/Repository/` (create when needed)
  - Templates in `templates/` (create when needed)
  - Configuration in `config/`

- Use correct namespaces when importing classes or services

- **Never put styles in Twig files**. All CSS must be in `.css` files only (in `public/css/` or via Asset Mapper). No inline styles or `<style>` blocks in templates.

- **All routes must use POST by default**. This prevents sensitive data (search criteria, patient identifiers, medical information) from appearing in URLs, browser history, or server logs. GET should only be used when strictly necessary (e.g., initial page load before any data is submitted, or redirects after form processing). If you believe a specific route would benefit from GET, ask the user for authorization before implementing it.
  - Search forms: always `method="post"` with CSRF protection
  - Data display after user action: POST with CSRF token
  - Simple page navigation (home, initial form display): GET is acceptable
  - Form submissions (create/edit/delete): POST (already standard)

- **Use Symfony MakerBundle** for generating code (install first: `composer require --dev symfony/maker-bundle`):
  ```bash
  docker compose exec php bin/console make:controller
  docker compose exec php bin/console make:entity
  docker compose exec php bin/console make:form
  docker compose exec php bin/console make:migration
  ```

---

## Language & Internationalization Guidelines

### Code Language: ENGLISH

All technical elements must be written in **English**:

- Variable names: `$patient`, `$donorType`, `$transplantDate`
- Method names: `getDonor()`, `setTransplantStatus()`, `calculateAge()`
- Class names: `DonorController`, `TransplantService`, `PatientRepository`
- Comments and documentation: `// Check if donor is compatible`
- Database column names should follow snake_case in English when creating new ones
- Constants and enums: `DONOR_TYPE_LIVING`, `STATUS_ACTIVE`
- Git commit messages: English

### User-Facing Content: FRENCH

The website is intended for **French users**. All content displayed to users must be in **French**:

- Labels: `'label' => 'Numéro de dossier'`
- Flash messages: `$this->addFlash('success', 'Patient créé avec succès')`
- Form placeholders: `'placeholder' => 'Ex: 2023-001234'`
- Error messages: `'message' => 'Le numéro de dossier est obligatoire'`
- Page titles, headings, navigation items
- Button text, confirmation dialogs

### Translation-Ready Architecture

The application is built to be **internationalization-ready** but currently deployed **only for French users**:

- Use Symfony's translation system when possible (`translations/messages.fr.yaml`)
- Keep hardcoded French strings in templates/forms for now, but structure code to facilitate future extraction
- Date formats should use French locale: `d/m/Y` for dates
- Number formats should use French conventions when applicable

### Example

```php
// ✅ CORRECT
class DonorController extends AbstractController
{
    /**
     * Display the list of living donors with their relationship status.
     */
    public function listLivingDonors(): Response
    {
        $donors = $this->donorRepository->findAllLiving();
        
        if (empty($donors)) {
            $this->addFlash('info', 'Aucun donneur vivant enregistré');
        }
        
        return $this->render('donor/list.html.twig', [
            'donors' => $donors,
            'pageTitle' => 'Liste des Donneurs Vivants',
        ]);
    }
}

// ❌ INCORRECT - French variable/method names
class ControlleurDonneur extends AbstractController
{
    public function listerDonneursVivants(): Response
    {
        $donneurs = $this->depotDonneur->trouverTousVivants();
        // ...
    }
}
```

---

## Role-Based Access Control

The application implements the following roles:

- `ROLE_USER`: Basic access, view-only for medical data
- `ROLE_NURSE`: Same as ROLE_USER (read-only)
- `ROLE_DOCTOR`: Can create, modify, and delete medical records
- `ROLE_TRANSPLANT_COORDINATOR`: Can create/edit/view donors, link donors to patients, NO patient data access
- `ROLE_TECH_ADMIN`: System/user management, can only create ROLE_USER profiles, NO access to patient medical data
- `ROLE_SUPER_ADMIN`: Inherits ROLE_TECH_ADMIN, full user/role management, can assign any role

### Role Hierarchy

```
ROLE_SUPER_ADMIN → ROLE_TECH_ADMIN → ROLE_USER
ROLE_DOCTOR → ROLE_USER
ROLE_NURSE → ROLE_USER
ROLE_TRANSPLANT_COORDINATOR → ROLE_USER
```

### Admin Privilege Restrictions

- **ROLE_SUPER_ADMIN**: Can create any user with any role, edit/delete all users
- **ROLE_TECH_ADMIN**: Can only create ROLE_USER profiles (no role checkboxes in form). Cannot edit/delete users with privileged roles (SUPER_ADMIN, TECH_ADMIN, DOCTOR, NURSE, TRANSPLANT_COORDINATOR)
- UserType form uses `is_super_admin` option to dynamically show/hide role selection

> **⚠️ Legal note (Art. L1110-4 CSP):** Technical administrators must NOT have access
> to patient medical data. Only healthcare professionals in the care team may view
> patient files. No role has blanket access to all patients.

Use `#[IsGranted('ROLE_DOCTOR')]` attributes on controller methods for protection.
Use `#[IsGranted('ROLE_TECH_ADMIN')]` for admin panel access (ROLE_SUPER_ADMIN inherits this).

### Patient Access Control

Per-patient access is enforced by `PatientAccessVoter` (attribute: `VIEW_PATIENT`), based on French health law (Art. L1110-4, L1110-12 CSP "équipe de soins"):

| User Type | Access Level |
|-----------|-------------|
| Any practitioner (doctor, nurse) | Only assigned patients (via `patient_authorized_user` join table) |
| Any practitioner with active BTG | Temporary emergency access (3h, justified, audited) |
| `ROLE_TECH_ADMIN` / `ROLE_SUPER_ADMIN` | **No patient access** (system management only) |
| `ROLE_TRANSPLANT_COORDINATOR` | **No patient access** (donor management only) |

> **Note:** The `isChuPractitioner` field is deprecated and no longer used for access control.
> All practitioners must be explicitly assigned to patients they need to access.
> `ROLE_MEDICAL_ADMIN` has been removed — replaced by break-the-glass.

- **Voters**: `WriteAccessVoter` (CAN_WRITE, CAN_DELETE) + `PatientAccessVoter` (VIEW_PATIENT)
- **Break-the-glass**: `BreakTheGlassAccess` entity + `BreakTheGlassController` for emergency access
- **Legal reference**: See `docs/PATIENT_ACCESS_LEGAL.md`

---

## Functional Specifications

This section describes the detailed business requirements from the specifications document.

### Application Context

The kidney transplant service at CHU XXX manages patients from the region and neighboring departments. The application provides a **shared patient file** accessible by both hospital practitioners and city nephrologists for longitudinal post-transplant follow-up.

### Authentication Requirements

> **⚠️ SIMULATED FEATURE** (BTS SIO Exercise)
> 
> Real CPS authentication requires official healthcare system integration not available for student projects. Standard form-based authentication is implemented instead.

- **Primary**: ~~CPS (Carte Professionnel de Santé) authentication~~ → Form-based login (simulated)
- **Fallback**: Standard username/password authentication
- **Activity Logging**: Track user identity, login time, and logout time
- **Future**: Plan for restricted user profiles with read-only access

### Main Entities

#### Patient

Patient identification and search criteria:
- Name (nom)
- First name (prénom)  
- Patient file number (numéro de dossier)
- City of residence (ville de résidence)
- Blood group with Rhesus factor (groupe sanguin + rhésus, e.g. "A+", "O-")

**Search behavior:**
- At least one criteria required
- Results sorted alphabetically by name
- Paginated display
- Confirmation required for >200 results

#### Patient File Access

Once a patient is selected, user can access:
- Medical history (antécédents)
- Consultations
- Transplants (greffes)
- Therapeutic Patient Education (ETP)
- Biological results

### Transplant (Greffe) Entity

A transplant links a patient (recipient) to a graft from a donor.

**Essential Information:**
- `transplantDate`: Date of transplant
- `rank`: Transplant rank (1st, 2nd, etc.)
- `donorType`: "living" | "deceased_encephalic" | "deceased_cardiac_arrest"

**Graft Details:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `isGraftFunctional` | boolean | Yes | |
| `graftEndDate` | datetime | No | Only if not functional |
| `graftEndCause` | string | No | Only if not functional |
| `transplantType` | enum | Yes | "Rein", "Rein donneur vivant", "Rein-pancréas", "Rein-foie", "Rein-coeur", "Autre" |
| `surgeonName` | string | No | |
| `declampingDate` | date | No | |
| `declampingTime` | time | No | |
| `harvestSide` | enum | Yes | "droit", "gauche" |
| `transplantSide` | enum | Yes | "droit", "gauche" |
| `peritonealPosition` | enum | Yes | "Extra Péritonéal", "Intra Péritonéal" |
| `totalIschemia` | duration | Yes | Hours:Minutes format |
| `anastomosisDuration` | integer | Yes | Minutes |
| `jjProbe` | boolean | Yes | Whether JJ probe was used |
| `comment` | text | No | Free text |
| `operativeReport` | file | No | Attached file (upload/view/delete) |

**Virological Status:**
| Field | Values | Required |
|-------|--------|----------|
| `cmvStatus` | "D-/R-", "D-/R+", "D+/R-", "D+/R+" | Yes |
| `ebvStatus` | "D-/R-", "D-/R+", "D+/R-", "D+/R+" | No |
| `toxoplasmosisStatus` | "R+", "R-" | No |

**HLA Incompatibility (values: 0, 1, 2):**
- `hlaA`: Required
- `hlaB`: Required
- `hlaCw`: Optional
- `hlaDR`: Required
- `hlaDQ`: Required
- `hlaDP`: Optional

**Immunological Risk:**
| Value | Display Color |
|-------|---------------|
| "Non immunisé" | Green |
| "Immunisé sans DSA" | Orange |
| "Immunisé DSA" | Red |
| "ABO incompatible" | Red |

**Immunosuppressive Conditioning:**
Values: "Advagraf", "Prograf", "Neoral", "Rapamune", "Certican", "Cellcept", "Myfortic", "Imurel", "Methylprednisolone", "Mabthera", "Ig IV", "Soliris", "Thymoglobulines", "Simulect", "Plasmaphérese", "Immuno absorption"

**Dialysis:**
- `dialysis`: boolean (required)
- `lastDialysisDate`: date (required if dialysis=true)

**Protocol:**
- `hasProtocol`: boolean
- Protocol file attachment if yes

### Donor Entity

### Donor Entity

> **Architecture Decision**: Donor data is stored as a **standalone relational entity** (`donor` table) with all specification fields as proper database columns. The Transplant entity has an optional `ManyToOne` relationship to Donor, plus a legacy `donorData` JSON column for backward compatibility. Donor pages are standalone at `/donors` (not nested under patient). Living donor names are encrypted using the `encrypted_string` Doctrine type.

#### Common Fields (Living & Deceased)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `cristalNumber` | string | Yes | National CRISTAL reference ID |
| `bloodGroup` | enum | Yes | "A", "B", "AB", "O" |
| `rhesus` | enum | Yes | "+", "-" |
| `sex` | enum | Yes | "M", "F" |
| `age` | integer | Yes | |
| `height` | integer | No | |
| `weight` | integer | No | |
| `patientComment` | text | No | |

**HLA Grouping (2-digit integers, admin-only edit after first save):**
- `hlaA`, `hlaB`, `hlaDR`, `hlaDQ`: Required
- `hlaCw`, `hlaDP`: Optional

**Serology (values: "+" or "-"):**
- `cmv`, `ebv`, `hiv`, `htlv`, `syphilis`, `hcv`, `agHbs`, `acHbs`, `acHbc`: Required
- `toxoplasmosis`: "+", "-", "ND" (optional)
- `arnc`, `dnaB`: Optional

**Surgical Details:**
- `surgeonName`, `clampingDate`, `clampingTime`, `harvestSide` (optional)
- Arterial info: main artery, upper polar, lower polar
- Venous info: vein, vein comment
- `perfusionMachine`: "Oui", "Non"
- `perfusionLiquid`: "Viaspan", "Celsior", "IgL", "Scott"

#### Living Donor Specific

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `lastName` | encrypted | Yes | |
| `firstName` | encrypted | Yes | |
| `relationshipType` | enum | Yes | "Parent", "Enfant", "2ème degré", "Conjoint", "Non apparenté", "Autre" |
| `relationshipComment` | text | No | |
| `bmi` | calculated | Yes | weight / height² |
| `creatinine` | float | Yes | |
| `calculatedClearance` | calculated | Yes | MDRD formula |
| `isotopicClearance` | float | Yes | |
| `proteinuria` | float | Yes | |
| `approach` | enum | Yes | "Lombotomie", "Cœlioscopie" |
| `robot` | boolean | Yes | |

**Clearance Formula (MDRD):**
```
186 × (creatinine_µmol × 0.0113)^(-1.154) × age^(-0.203) × (0.742 if female)
```

#### Deceased Donor Specific

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `originCity` | string | Yes | |
| `deathCause` | enum | Yes | "AVC hémorragique", "AVC ischémique", "AVP", "TC non AVP", "Anoxie", "Autre" |
| `deathCauseComment` | text | Yes | |
| `extendedCriteriaDonor` | boolean | Yes | DCE: ≥60 years OR ≥50 years with 2/3: HTA, creatinine≥132µmol/l, vascular death |
| `cardiacArrest` | boolean | Yes | |
| `cardiacArrestDuration` | integer | Yes | |
| `meanArterialPressure` | float | Yes | |
| `amines` | boolean | Yes | |
| `transfusion` | boolean | Yes | |
| `cgr`, `cpa`, `pfc` | integer | Yes if transfusion | |
| `creatinineArrival` | float | Yes | |
| `creatinineSample` | float | Yes | |
| `dfg` | calculated | Yes | GFR formula |
| `ureter` | enum | Yes | "1", "2" |
| `conservationLiquid` | enum | Yes | "Viaspan", "Celsior", "IGL", "Scott" |

**GFR Formula:**
```
Male: 175 × (creatinine / 88.4)^(-1.154) × age^(-0.203)
Female: 175 × (creatinine / 88.4)^(-1.154) × age^(-0.203) × 0.742
```

**Optional Atheroma Fields (all boolean):**
- Aorta atheroma, calcified aorta plaques
- Ostium artery atheroma, calcified ostium plaques
- Renal artery atheroma, calcified renal plaques
- Digestive wound
- Conservation liquid infection

### CRISTAL Integration

> **⚠️ SIMULATED FEATURE** (BTS SIO Exercise)
> 
> Real CRISTAL integration is not possible in this educational project. The application implements a **mock CRISTAL system** that simulates the expected behavior with fictional data.

The application simulates integration with the French national **CRISTAL** system (Référentiel national des prélèvements et greffes d'organes) for:
- Donor identification (simulated with mock CRISTAL numbers)
- Graft traceability (local tracking only)
- National reporting (not connected to real system)

### Business Rules

1. **Transplant Deletion**: Admin-only permission
2. **HLA Grouping Edit**: Admin-only after first validation
3. **Search Limit**: Confirm before displaying >200 results
4. **Conditional Fields**: 
   - Graft end date/cause only if graft not functional
   - Last dialysis date required if dialysis=true
   - CGR/CPA/PFC only if transfusion=true
5. **Calculated Fields**: BMI, clearance, GFR are auto-calculated
6. **Color Coding**: Immunological risk displayed with color indicators
