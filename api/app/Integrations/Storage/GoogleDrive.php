<?php

namespace App\Integrations\Storage;

use App\Exceptions\Storage\FileDownloadException;
use App\Services\Indexing\FilePrioritizer;
use Exception;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class GoogleDrive
{
    private Filesystem $fs;
    private Drive $drive;

    private const GOOGLE_NATIVE_TYPES = [
        'application/vnd.google-apps.document',     // Google Docs
        'application/vnd.google-apps.spreadsheet', // Google Sheets
        'application/vnd.google-apps.presentation',// Google Slides
        'application/vnd.google-apps.form',        // Google Forms
    ];

    private const EXPORT_FORMATS = [
        'application/vnd.google-apps.document' => 'text/plain',     // Export Docs as plain text
        'application/vnd.google-apps.spreadsheet' => 'text/csv',   // Export Sheets as CSV
        'application/vnd.google-apps.presentation' => 'text/plain', // Export Slides as plain text
        'application/vnd.google-apps.form' => 'text/plain',        // Export Forms as plain text
    ];

    public function __construct(private FilePrioritizer $prioritizer)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));;
        $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));;
        $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));;
        $client->setApplicationName('Horizontal');

        $this->drive = new Drive($client);
        $adapter = new GoogleDriveAdapter($this->drive);
        $this->fs = new Filesystem($adapter);
    }

    public function listDirectoryContents(string $directory = ''): array
    {
        $contents = $this->fs->listContents($directory, true);
        return $this->prioritizer->prioritize($contents);
    }

    public function downloadFile(FileAttributes $file)
    {
        try {
            if ($this->isGoogleNativeFile($file)) {
                $content = $this->exportGoogleNativeFile($file);
            } else {
                $content = $this->fs->read($file->path());
            }

            $result = Storage::put($file->path(), $content);
            if (!$result) {
                throw new FileDownloadException("Failed to write file to storage: " . json_encode($file));
            }
        } catch (Exception $e) {
            throw FileDownloadException::wrap($e);
        }
    }

    private function isGoogleNativeFile(FileAttributes $file): bool
    {
        return in_array($file->mimeType(), self::GOOGLE_NATIVE_TYPES);
    }

    private function exportGoogleNativeFile(FileAttributes $file): string
    {
        if (!isset(self::EXPORT_FORMATS[$file->mimeType()])) {
            throw new FileDownloadException("Unsupported Google native file type: {$file->mimeType()}");
        }

        $exportFormat = self::EXPORT_FORMATS[$file->mimeType()];;

        try {
            $response = $this->drive->files->export($file->extraMetadata()['id'], $exportFormat, [
                'alt' => 'media'
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $e) {
            throw FileDownloadException::wrap($e);
        }
    }
}
