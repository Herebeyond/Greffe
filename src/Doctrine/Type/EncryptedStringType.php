<?php

namespace App\Doctrine\Type;

use App\Service\EncryptionService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Custom Doctrine type for storing encrypted strings.
 * Data is automatically encrypted before storage and decrypted when retrieved.
 */
class EncryptedStringType extends Type
{
    public const ENCRYPTED_STRING = 'encrypted_string';

    private static ?EncryptionService $encryptionService = null;

    public static function setEncryptionService(EncryptionService $encryptionService): void
    {
        self::$encryptionService = $encryptionService;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // Encrypted strings are longer than original, use TEXT for safety
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (self::$encryptionService === null) {
            throw new \RuntimeException('EncryptionService not initialized for EncryptedStringType');
        }

        return self::$encryptionService->encrypt($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (self::$encryptionService === null) {
            throw new \RuntimeException('EncryptionService not initialized for EncryptedStringType');
        }

        return self::$encryptionService->decrypt($value);
    }

    public function getName(): string
    {
        return self::ENCRYPTED_STRING;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
