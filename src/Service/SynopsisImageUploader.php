<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SynopsisImageUploader
{
    private string $uploadDirectory;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDirectory,
    ) {
        $this->uploadDirectory = $projectDirectory . '/var/uploads/synopsis';
    }

    public function store(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('Le téléversement de la miniature a échoué.');
        }

        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new \RuntimeException('Le format de la miniature est invalide.'),
        };

        if (!is_dir($this->uploadDirectory)
            && !mkdir($this->uploadDirectory, 0750, true)
            && !is_dir($this->uploadDirectory)
        ) {
            throw new \RuntimeException('Le répertoire privé des miniatures ne peut pas être créé.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->move($this->uploadDirectory, $filename);

        return $filename;
    }

    public function remove(string $filename): void
    {
        if (!$this->isManagedFilename($filename)) {
            return;
        }

        $path = $this->uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function resolve(string $filename): ?string
    {
        if (!$this->isManagedFilename($filename)) {
            return null;
        }

        $managedDirectory = realpath($this->uploadDirectory);
        $path = realpath($this->uploadDirectory . DIRECTORY_SEPARATOR . $filename);
        if ($managedDirectory === false || $path === false) {
            return null;
        }

        if (!str_starts_with($path, $managedDirectory . DIRECTORY_SEPARATOR) || !is_file($path)) {
            return null;
        }

        return $path;
    }

    private function isManagedFilename(string $filename): bool
    {
        return preg_match('/\A[a-f0-9]{32}\.(?:png|jpg)\z/', $filename) === 1;
    }
}
