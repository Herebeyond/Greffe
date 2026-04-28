<?php

namespace App\Service;

use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as SymmetricCrypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;

/**
 * Service for encrypting and decrypting sensitive medical data.
 * Uses Halite (libsodium) for cryptographic operations.
 */
class EncryptionService
{
    private ?EncryptionKey $encryptionKey = null;
    private string $encryptionKeyPath;
    private bool $keyLoaded = false;
    private array $loggedFailureSignatures = [];

    public function __construct(string $encryptionKeyPath, private readonly LoggerInterface $logger)
    {
        $this->encryptionKeyPath = $encryptionKeyPath;
    }

    public function getKeyPath(): string
    {
        return $this->encryptionKeyPath;
    }

    public function getKeyFingerprint(): ?string
    {
        if (!is_file($this->encryptionKeyPath) || !is_readable($this->encryptionKeyPath)) {
            return null;
        }

        $hash = @hash_file('sha256', $this->encryptionKeyPath);

        return $hash === false ? null : $hash;
    }

    /**
     * @return array<string, mixed>
     */
    public function getKeyDiagnostics(): array
    {
        $path = $this->encryptionKeyPath;
        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $diagnostics = [
            'path' => $path,
            'exists' => $exists,
            'readable' => $readable,
            'loaded' => $this->keyLoaded,
            'fingerprint' => $this->getKeyFingerprint(),
            'size' => $exists ? @filesize($path) ?: null : null,
            'permissions' => $exists ? substr(sprintf('%o', @fileperms($path) ?: 0), -4) : null,
            'loadable' => false,
            'load_error' => null,
        ];

        if (!$exists) {
            $diagnostics['load_error'] = 'Key file does not exist';

            return $diagnostics;
        }

        if (!$readable) {
            $diagnostics['load_error'] = 'Key file is not readable';

            return $diagnostics;
        }

        try {
            $this->getKey();
            $diagnostics['loaded'] = true;
            $diagnostics['loadable'] = true;
        } catch (\Throwable $exception) {
            $diagnostics['load_error'] = sprintf('%s: %s', $exception::class, $exception->getMessage());
        }

        return $diagnostics;
    }

    /**
     * @return array<string, mixed>
     */
    public function inspectValue(?string $value): array
    {
        if ($value === null || $value === '') {
            return [
                'status' => 'empty',
                'looks_encrypted' => false,
                'plaintext' => $value,
                'error' => null,
            ];
        }

        $looksEncrypted = $this->isEncrypted($value);

        if (!$looksEncrypted) {
            return [
                'status' => 'plain_text',
                'looks_encrypted' => false,
                'plaintext' => $value,
                'error' => null,
            ];
        }

        try {
            $hiddenString = SymmetricCrypto::decrypt($value, $this->getKey());

            return [
                'status' => 'decrypted',
                'looks_encrypted' => true,
                'plaintext' => $hiddenString->getString(),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'decrypt_failed',
                'looks_encrypted' => true,
                'plaintext' => $value,
                'error' => sprintf('%s: %s', $exception::class, $exception->getMessage()),
            ];
        }
    }

    /**
     * Lazy-load the encryption key when needed.
     */
    private function getKey(): EncryptionKey
    {
        if (!$this->keyLoaded) {
            if (!file_exists($this->encryptionKeyPath)) {
                throw new \RuntimeException(
                    sprintf('Encryption key file not found at: %s. Run "bin/console app:generate-encryption-key" to generate one.', $this->encryptionKeyPath)
                );
            }

            $this->encryptionKey = KeyFactory::loadEncryptionKey($this->encryptionKeyPath);
            $this->keyLoaded = true;
            $this->logger->info('Encryption key loaded successfully.', [
                'path' => $this->encryptionKeyPath,
                'fingerprint' => $this->getKeyFingerprint(),
            ]);
        }

        return $this->encryptionKey;
    }

    /**
     * Check if the encryption key is available.
     */
    public function isKeyAvailable(): bool
    {
        return file_exists($this->encryptionKeyPath);
    }

    /**
     * Encrypt a string value.
     */
    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        $hiddenString = new HiddenString($plaintext);
        
        return SymmetricCrypto::encrypt($hiddenString, $this->getKey());
    }

    /**
     * Decrypt an encrypted string value.
     */
    public function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return $ciphertext;
        }

        $inspection = $this->inspectValue($ciphertext);

        if ($inspection['status'] === 'decrypted') {
            return $inspection['plaintext'];
        }

        if ($inspection['status'] === 'decrypt_failed') {
            $signature = md5($inspection['error'] . '|' . substr($ciphertext, 0, 24) . '|' . ($this->getKeyFingerprint() ?? 'no-key'));

            if (!isset($this->loggedFailureSignatures[$signature])) {
                $this->loggedFailureSignatures[$signature] = true;
                $this->logger->warning('Encrypted value could not be decrypted. Returning ciphertext unchanged.', [
                    'path' => $this->encryptionKeyPath,
                    'fingerprint' => $this->getKeyFingerprint(),
                    'ciphertext_prefix' => substr($ciphertext, 0, 24),
                    'error' => $inspection['error'],
                ]);
            }
        }

        return $ciphertext;
    }

    /**
     * Check if a string appears to be encrypted (starts with Halite header).
     */
    public function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // Halite encrypted strings have a specific format/header
        return str_starts_with($value, 'MUIFA');
    }
}
