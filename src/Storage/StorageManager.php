<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Storage;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageManager
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Store a file on the configured disk.
     */
    public function store(string $sourcePath, string $folder, ?string $extension = null): string
    {
        $extension = $extension ?? pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';
        $filename = $this->generateFilename($extension);
        $storagePath = rtrim($folder, '/').'/'.$filename;

        Storage::disk($this->getDisk())->put($storagePath, file_get_contents($sourcePath));

        return $storagePath;
    }

    /**
     * Delete a file from the configured disk.
     */
    public function delete(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        return Storage::disk($this->getDisk())->delete($path);
    }

    /**
     * Get the public URL for a file.
     */
    public function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->getDisk());

        return $disk->url($path);
    }

    /**
     * Check if a file exists on the configured disk.
     */
    public function exists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        return Storage::disk($this->getDisk())->exists($path);
    }

    public function getDisk(): string
    {
        return $this->config['disk'] ?? 'public';
    }

    private function generateFilename(string $extension): string
    {
        $naming = $this->config['naming'] ?? 'timestamp_hex';

        $name = match ($naming) {
            'uuid' => (string) Str::uuid(),
            'ulid' => (string) Str::ulid(),
            default => time().'_'.bin2hex(random_bytes(8)),
        };

        return $name.'.'.$extension;
    }
}
