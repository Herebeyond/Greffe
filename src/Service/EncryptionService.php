<?php

namespace App\Service;

use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as SymmetricCrypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Service for encrypting and decrypting sensitive medical data.
 * Uses Halite (libsodium) for cryptographic operations.
 */
class EncryptionService
{
    private ?EncryptionKey $encryptionKey = null;
    private string $encryptionKeyPath;
    private bool $keyLoaded = false;

    public function __construct(string $encryptionKeyPath)
    {
        $this->encryptionKeyPath = $encryptionKeyPath;
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

        try {
            $hiddenString = SymmetricCrypto::decrypt($ciphertext, $this->getKey());
            
            return $hiddenString->getString();
        } catch (\Exception $e) {
            // If decryption fails, the data might not be encrypted (migration period)
            // Log this for monitoring but return the original value
            return $ciphertext;
        }
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
