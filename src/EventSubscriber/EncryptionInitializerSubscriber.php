<?php

namespace App\EventSubscriber;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\EncryptionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Initializes the encryption service for Doctrine encrypted types.
 * This is needed because Doctrine types are statically loaded before the service container is available.
 */
class EncryptionInitializerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255], // High priority
            ConsoleEvents::COMMAND => ['onConsoleCommand', 255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->initializeEncryption();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        // Skip initialization for commands that don't need encryption
        $command = $event->getCommand();
        $skipCommands = [
            'app:generate-encryption-key',
            'cache:clear',
            'cache:warmup',
            'debug:container',
            'debug:autowiring',
        ];
        
        if ($command !== null && in_array($command->getName(), $skipCommands, true)) {
            return;
        }

        $this->initializeEncryption();
    }

    private function initializeEncryption(): void
    {
        $diagnostics = $this->encryptionService->getKeyDiagnostics();

        if ($diagnostics['loadable']) {
            EncryptedStringType::setEncryptionService($this->encryptionService);

            $this->logger->info('EncryptedStringType initialized.', [
                'path' => $diagnostics['path'],
                'fingerprint' => $diagnostics['fingerprint'],
            ]);

            return;
        }

        $this->logger->warning('EncryptedStringType could not be initialized.', [
            'path' => $diagnostics['path'],
            'exists' => $diagnostics['exists'],
            'readable' => $diagnostics['readable'],
            'permissions' => $diagnostics['permissions'],
            'fingerprint' => $diagnostics['fingerprint'],
            'load_error' => $diagnostics['load_error'],
        ]);
    }
}
