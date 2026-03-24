<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor;

use Illuminate\Http\UploadedFile;
use JustSmileToo\FileCompressor\Compressors\CompressorInterface;
use JustSmileToo\FileCompressor\Compressors\ImageCompressor;
use JustSmileToo\FileCompressor\Compressors\PdfCompressor;
use JustSmileToo\FileCompressor\Compressors\VideoCompressor;
use JustSmileToo\FileCompressor\Dto\CompressionResult;
use JustSmileToo\FileCompressor\Dto\UploadResult;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;
use JustSmileToo\FileCompressor\Exceptions\UnsupportedFileTypeException;
use JustSmileToo\FileCompressor\Storage\StorageManager;
use SplFileInfo;

class FileCompressor
{
    /** @var list<CompressorInterface> */
    private array $compressors;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        ImageCompressor $imageCompressor,
        PdfCompressor $pdfCompressor,
        VideoCompressor $videoCompressor,
        private readonly StorageManager $storage,
        array $config,
    ) {
        $this->compressors = [$imageCompressor, $pdfCompressor, $videoCompressor];
        $this->config = $config;
    }

    /**
     * Auto-detect the file type and compress it with the appropriate compressor.
     *
     * @param  array<string, mixed>  $options  Per-call overrides passed to the compressor.
     */
    public function compress(UploadedFile|SplFileInfo|string $file, array $options = []): CompressionResult
    {
        $filePath = $this->resolveFilePath($file);
        $mimeType = $this->resolveMimeType($file, $filePath);

        if (! $this->isEnabled()) {
            return $this->noopResult($filePath, $mimeType);
        }

        $compressor = $this->resolveCompressor($mimeType);

        return $compressor->compress($filePath, $options);
    }

    /**
     * Compress a file as an image, bypassing MIME detection.
     *
     * @param  array<string, mixed>  $options
     */
    public function compressImage(UploadedFile|SplFileInfo|string $file, array $options = []): CompressionResult
    {
        return $this->compressWithType(ImageCompressor::class, $file, $options);
    }

    /**
     * Compress a file as a PDF, bypassing MIME detection.
     *
     * @param  array<string, mixed>  $options
     */
    public function compressPdf(UploadedFile|SplFileInfo|string $file, array $options = []): CompressionResult
    {
        return $this->compressWithType(PdfCompressor::class, $file, $options);
    }

    /**
     * Compress a file as a video, bypassing MIME detection.
     *
     * @param  array<string, mixed>  $options
     */
    public function compressVideo(UploadedFile|SplFileInfo|string $file, array $options = []): CompressionResult
    {
        return $this->compressWithType(VideoCompressor::class, $file, $options);
    }

    /**
     * Determine if the given MIME type is supported by any compressor.
     */
    public function isSupported(string $mimeType): bool
    {
        foreach ($this->compressors as $compressor) {
            if ($compressor->supports($mimeType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if compression is globally enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    /**
     * Compress and store a file in one call. Returns the storage path.
     *
     * @param  array<string, mixed>  $options  Compressor options (preset, quality, max_width, etc.)
     */
    public function upload(UploadedFile|SplFileInfo|string $file, string $folder, array $options = []): UploadResult
    {
        $filePath = $this->resolveFilePath($file);
        $mimeType = $this->resolveMimeType($file, $filePath);

        if ($this->isEnabled() && $this->isSupported($mimeType)) {
            $result = $this->compress($file, $options);
            $extension = $this->resolveOutputExtension($result, $options, $filePath);
            $storagePath = $this->storage->store($result->path, $folder, $extension);
            @unlink($result->path);

            return new UploadResult(
                path: $storagePath,
                originalSize: $result->originalSize,
                compressedSize: $result->compressedSize,
                mimeType: $result->mimeType,
                wasCompressed: $result->wasCompressed,
            );
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: null;
        $storagePath = $this->storage->store($filePath, $folder, $extension);
        $size = filesize($filePath) ?: 0;

        return new UploadResult(
            path: $storagePath,
            originalSize: $size,
            compressedSize: $size,
            mimeType: $mimeType,
            wasCompressed: false,
        );
    }

    /**
     * Delete a file from the configured storage disk.
     */
    public function delete(?string $path): bool
    {
        return $this->storage->delete($path);
    }

    /**
     * Get the public URL of a stored file.
     */
    public function url(?string $path): ?string
    {
        return $this->storage->url($path);
    }

    /**
     * Check if a file exists on the configured storage disk.
     */
    public function exists(?string $path): bool
    {
        return $this->storage->exists($path);
    }

    /**
     * @param  class-string<CompressorInterface>  $compressorClass
     * @param  array<string, mixed>  $options
     */
    private function compressWithType(string $compressorClass, UploadedFile|SplFileInfo|string $file, array $options): CompressionResult
    {
        $filePath = $this->resolveFilePath($file);

        if (! $this->isEnabled()) {
            $mimeType = $this->resolveMimeType($file, $filePath);

            return $this->noopResult($filePath, $mimeType);
        }

        foreach ($this->compressors as $compressor) {
            if ($compressor instanceof $compressorClass) {
                return $compressor->compress($filePath, $options);
            }
        }

        throw new CompressionException("Compressor not found: {$compressorClass}");
    }

    private function resolveFilePath(UploadedFile|SplFileInfo|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getRealPath() ?: $file->getPathname();
        }

        if ($file instanceof SplFileInfo) {
            return $file->getRealPath() ?: $file->getPathname();
        }

        return $file;
    }

    private function resolveMimeType(UploadedFile|SplFileInfo|string $file, string $filePath): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getMimeType() ?: $file->getClientMimeType();
        }

        $mime = mime_content_type($filePath);

        return $mime !== false ? $mime : 'application/octet-stream';
    }

    private function resolveCompressor(string $mimeType): CompressorInterface
    {
        foreach ($this->compressors as $compressor) {
            if ($compressor->supports($mimeType)) {
                return $compressor;
            }
        }

        throw UnsupportedFileTypeException::make($mimeType);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveOutputExtension(CompressionResult $result, array $options, string $originalPath): string
    {
        $convertTo = $options['convert_to'] ?? null;

        if ($convertTo !== null) {
            return $convertTo === 'jpeg' ? 'jpg' : $convertTo;
        }

        if (isset($options['preset'])) {
            $presets = $this->config['image']['presets'] ?? [];
            $preset = $presets[$options['preset']] ?? [];
            $presetConvert = $preset['convert_to'] ?? null;

            if ($presetConvert !== null) {
                return $presetConvert === 'jpeg' ? 'jpg' : $presetConvert;
            }
        }

        return pathinfo($result->path, PATHINFO_EXTENSION) ?: (pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'bin');
    }

    private function noopResult(string $filePath, string $mimeType): CompressionResult
    {
        $size = filesize($filePath) ?: 0;

        return new CompressionResult(
            path: $filePath,
            originalSize: $size,
            compressedSize: $size,
            mimeType: $mimeType,
            wasCompressed: false,
        );
    }
}
