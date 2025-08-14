<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileManagerService
{
    private string $uploadsDirectory;
    private Filesystem $filesystem;
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'application/x-pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];

    public function __construct(string $projectDir)
    {
        $this->uploadsDirectory = $projectDir . '/public/uploads/attachments';
        $this->filesystem = new Filesystem();
    }

    public function getFolderPath(string $type, int $id): string
    {
        $folder = $this->uploadsDirectory . '/' . $type . '-' . $id;
        
        // Create folder if it doesn't exist
        if (!$this->filesystem->exists($folder)) {
            $this->filesystem->mkdir($folder, 0755);
        }
        
        return $folder;
    }

    public function uploadFile(UploadedFile $file, string $type, int $id): array
    {
        // Validate mime type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \Exception('Type de fichier non autorisé: ' . $mimeType);
        }

        // Get file size and original name before moving
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();

        // Validate file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            throw new \Exception('Le fichier est trop volumineux. Taille maximale: 10MB');
        }

        $folder = $this->getFolderPath($type, $id);
        $originalFilename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        // Sanitize filename
        $slugger = new AsciiSlugger();
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $this->getUniqueFilename($folder, $safeFilename, $extension);
        
        // Move file to destination
        $file->move($folder, $newFilename);
        
        // Get file info for response
        $filePath = $folder . '/' . $newFilename;
        $publicPath = '/uploads/attachments/' . $type . '-' . $id . '/' . $newFilename;
        
        return [
            'name' => $newFilename,
            'originalName' => $originalName,
            'url' => $publicPath,
            'size' => $this->formatFileSize($fileSize),
            'type' => $this->getFileType($extension),
            'extension' => $extension,
            'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
    }

    public function deleteFile(string $filename, string $type, int $id): void
    {
        $folder = $this->getFolderPath($type, $id);
        $filePath = $folder . '/' . $filename;
        
        if (!$this->filesystem->exists($filePath)) {
            throw new \Exception('Fichier introuvable');
        }
        
        $this->filesystem->remove($filePath);
    }

    public function getFiles(string $folder): array
    {
        $files = [];
        
        if (!is_dir($folder)) {
            return $files;
        }
        
        $iterator = new \DirectoryIterator($folder);
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            
            $extension = strtolower($file->getExtension());
            $relativePath = str_replace($this->uploadsDirectory, '/uploads/attachments', $folder . '/' . $file->getFilename());
            
            $files[] = [
                'name' => $file->getFilename(),
                'url' => $relativePath,
                'size' => $this->formatFileSize($file->getSize()),
                'type' => $this->getFileType($extension),
                'extension' => $extension,
                'modifiedAt' => date('Y-m-d H:i:s', $file->getMTime()),
                'isImage' => $this->isImage($extension)
            ];
        }
        
        // Sort by modification date (newest first)
        usort($files, function($a, $b) {
            return strcmp($b['modifiedAt'], $a['modifiedAt']);
        });
        
        return $files;
    }

    private function getFileType(string $extension): string
    {
        $types = [
            'pdf' => 'PDF',
            'doc' => 'Word',
            'docx' => 'Word',
            'xls' => 'Excel',
            'xlsx' => 'Excel',
            'ppt' => 'PowerPoint',
            'pptx' => 'PowerPoint',
            'jpg' => 'Image',
            'jpeg' => 'Image',
            'png' => 'Image',
            'webp' => 'Image',
            'txt' => 'Texte',
        ];
        
        return $types[strtolower($extension)] ?? 'Fichier';
    }

    private function isImage(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp']);
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function deleteEventAttachments(int $eventId): void
    {
        // List of possible attachment types for events
        $attachmentTypes = ['sender', 'event', 'email', 'template'];
        
        foreach ($attachmentTypes as $type) {
            $folderPath = $this->uploadsDirectory . '/' . $type . '-' . $eventId;
            
            if ($this->filesystem->exists($folderPath)) {
                $this->filesystem->remove($folderPath);
            }
        }
    }

    private function getUniqueFilename(string $folder, string $filename, string $extension): string
    {
        $baseFilename = $filename . '.' . $extension;
        $finalFilename = $baseFilename;
        $counter = 1;
        
        // Check if file exists and increment counter if needed
        while (file_exists($folder . '/' . $finalFilename)) {
            $finalFilename = $filename . ' (' . $counter . ').' . $extension;
            $counter++;
        }
        
        return $finalFilename;
    }
}