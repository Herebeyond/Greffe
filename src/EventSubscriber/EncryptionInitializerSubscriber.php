<?php

namespace App\EventSubscriber;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\EncryptionService;
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
        // Only initialize if the key is available
        if ($this->encryptionService->isKeyAvailable()) {
            EncryptedStringType::setEncryptionService($this->encryptionService);
        }
    }
}
