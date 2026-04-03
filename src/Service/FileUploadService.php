<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
        private string $uploadDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Upload multiple files and return an array of generated filenames.
     *
     * @param UploadedFile[] $files
     * @return string[]
     * @throws FileException if any upload fails or MIME type is not allowed
     */
    public function uploadMultiple(array $files, string $subdirectory): array
    {
        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $this->upload($file, $subdirectory);
        }
        return $filenames;
    }

    /**
     * Delete multiple files from the upload directory.
     *
     * @param string[] $filenames
     */
    public function deleteMultiple(array $filenames, string $subdirectory): void
    {
        foreach ($filenames as $filename) {
            $this->delete($filename, $subdirectory);
        }
    }

    /**
     * Upload a file and return the generated filename.
     *
     * @throws FileException if the upload fails or MIME type is not allowed
     */
    public function upload(UploadedFile $file, string $subdirectory): string
    {
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new FileException(sprintf(
                'Type de fichier non autorisé : %s. Types acceptés : PDF, JPEG, PNG, GIF, WebP.',
                $mimeType
            ));
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $targetDirectory = $this->getTargetDirectory($subdirectory);

        $file->move($targetDirectory, $newFilename);

        return $newFilename;
    }

    /**
     * Delete a file from the upload directory.
     */
    public function delete(string $filename, string $subdirectory): void
    {
        $filePath = $this->getFilePath($filename, $subdirectory);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Get the full path to an uploaded file.
     */
    public function getFilePath(string $filename, string $subdirectory): string
    {
        return $this->getTargetDirectory($subdirectory) . '/' . basename($filename);
    }

    private function getTargetDirectory(string $subdirectory): string
    {
        $path = $this->uploadDirectory . '/' . $subdirectory;

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return $path;
    }
}
