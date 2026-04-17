<?php

namespace App\Command;

use App\Service\EncryptionService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:decrypt-user-fields',
    description: 'Decrypt name, surname and cristal_id fields in the user table (one-time migration)',
)]
class DecryptUserFieldsCommand extends Command
{
    public function __construct(
        private EncryptionService $encryptionService,
        private Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->encryptionService->isKeyAvailable()) {
            $io->error('Encryption key not found. Cannot decrypt.');
            return Command::FAILURE;
        }

        $users = $this->connection->fetchAllAssociative('SELECT id, name, surname, cristal_id FROM "user"');

        $io->info(sprintf('Found %d users to process.', count($users)));

        $updated = 0;
        foreach ($users as $user) {
            $decryptedName = $this->decryptValue($user['name']);
            $decryptedSurname = $this->decryptValue($user['surname']);
            $decryptedCristalId = $this->decryptValue($user['cristal_id']);

            // Only update if at least one field was actually encrypted
            if ($decryptedName !== $user['name']
                || $decryptedSurname !== $user['surname']
                || $decryptedCristalId !== $user['cristal_id']
            ) {
                $this->connection->executeStatement(
                    'UPDATE "user" SET name = :name, surname = :surname, cristal_id = :cristalId WHERE id = :id',
                    [
                        'name' => $decryptedName,
                        'surname' => $decryptedSurname,
                        'cristalId' => $decryptedCristalId,
                        'id' => $user['id'],
                    ]
                );
                $io->writeln(sprintf('  Decrypted user #%d: %s %s', $user['id'], $decryptedSurname, $decryptedName));
                $updated++;
            } else {
                $io->writeln(sprintf('  User #%d already plain text, skipping.', $user['id']));
            }
        }

        $io->success(sprintf('Done. %d/%d users decrypted.', $updated, count($users)));

        return Command::SUCCESS;
    }

    private function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return $this->encryptionService->decrypt($value);
        } catch (\Exception) {
            // Already plain text
            return $value;
        }
    }
}
