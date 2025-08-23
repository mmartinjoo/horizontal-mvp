<?php

namespace App\Services\Indexing;

use App\Integrations\Storage\File;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;

class FilePrioritizer
{
    public function prioritize2(Generator $files): array
    {
        $highQueue = [];
        $mediumQueue = [];
        $lowQueue = [];

        /** @var File $file */
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->isMediaFile() || $file->isZipFile()) {
                continue;
            }

            if ($file->isSmallerThan(10) && $file->wasUsedIn(30)) {
                $highQueue[] = $file;
                continue;
            }

            if ($file->isSmallerThan(10) && $file->wasUsedIn(90)) {
                $mediumQueue[] = $file;
                continue;
            }

            if ($file->isSmallerThan(100) && $file->wasUsedIn(30)) {
                $lowQueue[] = $file;
                continue;
            }
        }

        return [
            'high' => collect($highQueue)->sortByDesc('lastUsedAt'),
            'medium' => collect($mediumQueue)->sortByDesc('lastUsedAt'),
            'low' => collect($lowQueue)->sortByDesc('lastUsedAt'),
        ];
    }

    /**
     * @param DirectoryListing<FileAttributes> $listing
     * @returns array<string, Collection<FileAttributes>>
     */
    public function prioritize(DirectoryListing $listing): array
    {
        $highQueue = [];
        $mediumQueue = [];
        $lowQueue = [];

        /** @var StorageAttributes $file */
        foreach ($listing as $file) {
            if (!$file instanceof FileAttributes || !$file->isFile()) {
                continue;
            }

            if ($this->isMediaFile($file) || $this->isZipFile($file)) {
                continue;
            }

            // Small files from last month
            if ($file->fileSize() <= 10*1024*1024
                && $file->lastModified() >= time() - 3600*24*30
            ) {
                $highQueue[] = $file;
                continue;
            }

            // Small files from last 3 months
            if ($file->fileSize() <= 10*1024*1024
                && $file->lastModified() >= time() - 3600*24*90
            ) {
                $mediumQueue[] = $file;
                continue;
            }

            // Medium files from last month
            if ($file->fileSize() <= 100*1024*1024
                && $file->lastModified() >= time() - 3600*24*30
            ) {
                $lowQueue[] = $file;
            }
        }

        return [
            'high' => collect($highQueue)->sortByDesc('last_modified'),
            'medium' => collect($mediumQueue)->sortByDesc('last_modified'),
            'low' => collect($lowQueue)->sortByDesc('last_modified'),
        ];
    }

    private function isMediaFile(FileAttributes $file): bool
    {
        return Str::startsWith($file->mimeType(), 'image')
            || Str::startsWith($file->mimeType(), 'audio')
            || Str::startsWith($file->mimeType(), 'video');
    }

    private function isZipFile(FileAttributes $file): bool
    {
        return Str::contains($file->mimeType(), 'zip');
    }
}
