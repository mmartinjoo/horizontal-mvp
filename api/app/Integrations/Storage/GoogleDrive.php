<?php

namespace App\Integrations\Storage;

use App\Exceptions\Storage\FileDownloadException;
use App\Services\Indexing\FilePrioritizer;
use Exception;
use Generator;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;
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

    public function listDirectoryContents(string $directory = ''): Generator
    {
        $listing = $this->fs->listContents($directory, true);
        $files = File::fromDirectoryListing($listing);
        foreach ($files as $file) {
            $dates = $this->getDates($file);
            $file->setCreatedAt($dates->createdTime);
            $file->setUpdatedAt($dates->modifiedTime);
            $file->setViewedAt($dates->viewedByMeTime);

            yield $file;
        }
    }

    public function downloadFile(File $file)
    {
        try {
            if ($this->isGoogleNativeFile($file)) {
                $content = $this->exportGoogleNativeFile($file);
                $result = Storage::put($file->path(), $content);
            } else {
                $stream = $this->fs->readStream($file->path());
                $result = Storage::writeStream($file->path(), $stream);
            }

            if (!$result) {
                throw new FileDownloadException("Failed to write file to storage: " . json_encode($file));
            }
        } catch (Exception $e) {
            throw FileDownloadException::wrap($e);
        }
    }

    private function isGoogleNativeFile(File $file): bool
    {
        return in_array($file->mimeType(), self::GOOGLE_NATIVE_TYPES);
    }

    private function exportGoogleNativeFile(File $file): string
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

    private function getDates(File $file): DriveFile
    {
        $fields = 'modifiedTime,createdTime,viewedByMeTime';
        return $this->drive->files->get($file->extraMetadata()['id'], ['fields' => $fields]);
    }
}
