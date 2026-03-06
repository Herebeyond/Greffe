<?php

namespace App\Command;

use ParagonIE\Halite\KeyFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-encryption-key',
    description: 'Generate a new encryption key for sensitive data encryption',
)]
class GenerateEncryptionKeyCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite if key already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $keyPath = $this->projectDir . '/var/encryption.key';
        
        if (file_exists($keyPath) && !$input->getOption('force')) {
            $io->error('Encryption key already exists at: ' . $keyPath);
            $io->warning('Use --force to overwrite (WARNING: this will make existing encrypted data unreadable!)');
            
            return Command::FAILURE;
        }

        // Generate a new encryption key
        $encryptionKey = KeyFactory::generateEncryptionKey();
        
        // Save the key to file
        KeyFactory::save($encryptionKey, $keyPath);
        
        // Secure the file permissions (Unix only, no effect on Windows)
        if (function_exists('chmod')) {
            chmod($keyPath, 0600);
        }

        $io->success('Encryption key generated successfully!');
        $io->note([
            'Key saved to: ' . $keyPath,
            'IMPORTANT: Back up this key securely. If lost, encrypted data cannot be recovered.',
            'In production, store this key outside of the application directory.',
        ]);

        return Command::SUCCESS;
    }
}
