<?php

namespace App\Command;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\EncryptionService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-encryption',
    description: 'Inspect encryption key loading, Doctrine type initialization, and sample encrypted data decryption.',
)]
class DebugEncryptionCommand extends Command
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of sample rows to inspect per table', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));

        $io->title('Diagnostic de chiffrement');

        $this->renderKeyDiagnostics($io);
        $this->renderDoctrineDiagnostics($io);
        $this->renderEnvironmentHints($io);
        $this->inspectTable($io, 'patient', ['last_name', 'first_name'], $limit);
        $this->inspectTable($io, '"user"', ['name', 'surname', 'cristal_id'], $limit);
        $this->renderCommonCauses($io);

        return Command::SUCCESS;
    }

    private function renderKeyDiagnostics(SymfonyStyle $io): void
    {
        $diagnostics = $this->encryptionService->getKeyDiagnostics();

        $io->section('Clé configurée');
        $io->definitionList(
            ['Chemin résolu', $diagnostics['path']],
            ['Variable ENCRYPTION_KEY_PATH', getenv('ENCRYPTION_KEY_PATH') ?: '(non définie)'],
            ['Fichier présent', $diagnostics['exists'] ? 'oui' : 'non'],
            ['Lisible', $diagnostics['readable'] ? 'oui' : 'non'],
            ['Permissions', $diagnostics['permissions'] ?? '(indisponibles)'],
            ['Taille', $diagnostics['size'] !== null ? (string) $diagnostics['size'] . ' octets' : '(indisponible)'],
            ['Empreinte SHA-256', $diagnostics['fingerprint'] ?? '(indisponible)'],
            ['Clé chargeable', $diagnostics['loadable'] ? 'oui' : 'non'],
            ['Erreur de chargement', $diagnostics['load_error'] ?? '(aucune)'],
        );
    }

    private function renderDoctrineDiagnostics(SymfonyStyle $io): void
    {
        $io->section('Doctrine / type chiffré');
        $io->definitionList(
            ['EncryptedStringType initialisé', EncryptedStringType::hasEncryptionService() ? 'oui' : 'non'],
        );
    }

    private function renderEnvironmentHints(SymfonyStyle $io): void
    {
        $io->section('Contexte d\'exécution');
        $io->definitionList(
            ['APP_ENV', $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '(inconnu)'],
            ['PHP_SAPI', PHP_SAPI],
        );
    }

    private function inspectTable(SymfonyStyle $io, string $tableName, array $columns, int $limit): void
    {
        $io->section(sprintf('Échantillons de %s', $tableName));

        try {
            $selects = array_merge(['id'], $columns);
            $sql = sprintf('SELECT %s FROM %s ORDER BY id ASC LIMIT %d', implode(', ', $selects), $tableName, $limit);
            $rows = $this->connection->fetchAllAssociative($sql);
        } catch (\Throwable $exception) {
            $io->warning(sprintf('Impossible de lire %s: %s', $tableName, $exception->getMessage()));

            return;
        }

        if ($rows === []) {
            $io->text('Aucune ligne à inspecter.');

            return;
        }

        $table = [];

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $inspection = $this->encryptionService->inspectValue($row[$column] ?? null);
                $table[] = [
                    (string) $row['id'],
                    $column,
                    $inspection['looks_encrypted'] ? 'oui' : 'non',
                    $inspection['status'],
                    $this->preview((string) ($row[$column] ?? '')),
                    $this->preview((string) ($inspection['plaintext'] ?? '')),
                    $inspection['error'] ?? '',
                ];
            }
        }

        $io->table(
            ['ID', 'Champ', 'Halite ?', 'Statut', 'Valeur brute', 'Valeur testée', 'Erreur'],
            $table,
        );
    }

    private function renderCommonCauses(SymfonyStyle $io): void
    {
        $io->section('Causes probables');
        $io->listing([
            'La clé courante ne correspond pas à celle qui a servi à chiffrer les données historiques.',
            'La variable ENCRYPTION_KEY_PATH pointe vers un autre fichier que prévu.',
            'Le fichier de clé existe mais ne peut pas être lu correctement dans le conteneur.',
            'EncryptedStringType n\'a pas été initialisé avant la lecture Doctrine.',
            'Les données en base sont déjà en clair ou ont été corrompues.',
        ]);
    }

    private function preview(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return mb_strlen($value) > 60 ? mb_substr($value, 0, 57) . '...' : $value;
    }
}