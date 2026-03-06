# Copilot Instructions - Kidney Transplant Management Platform

## Project Overview

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
│   ├── Controller/         # Controllers (empty)
│   └── Kernel.php          # Application kernel
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
- `ROLE_NURSE`: Same as ROLE_USER
- `ROLE_DOCTOR`: Can create and modify medical records
- `ROLE_ADMIN`: Full access including deletion and system configuration
- `ROLE_PATIENT`: Patient-specific access

Use `#[IsGranted('ROLE_DOCTOR')]` attributes on controller methods for protection.
