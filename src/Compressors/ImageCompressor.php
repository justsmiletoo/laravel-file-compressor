<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Compressors;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use JustSmileToo\FileCompressor\Dto\CompressionResult;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;

class ImageCompressor implements CompressorInterface
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

    public function compress(string $filePath, array $options = []): CompressionResult
    {
        $originalSize = filesize($filePath);

        if ($originalSize === false) {
            throw new CompressionException("Unable to read file: {$filePath}");
        }

        $mimeType = mime_content_type($filePath);

        if ($mimeType === false) {
            throw new CompressionException("Unable to detect MIME type: {$filePath}");
        }

        $quality = $options['quality'] ?? $this->config['quality'];
        $maxWidth = $options['max_width'] ?? $this->config['max_width'];
        $maxHeight = $options['max_height'] ?? $this->config['max_height'];
        $convertTo = $options['convert_to'] ?? $this->config['convert_to'];

        $driver = $this->resolveDriver();
        $manager = new ImageManager($driver);
        $image = $manager->read($filePath);

        $image->scaleDown(width: $maxWidth, height: $maxHeight);

        $encoder = $this->resolveEncoder($convertTo, $mimeType, $quality);
        $encoded = $image->encode($encoder);

        $outputExtension = $this->resolveExtension($convertTo, $filePath);
        $outputPath = $this->generateTempPath($outputExtension);

        $encoded->save($outputPath);

        $compressedSize = filesize($outputPath);

        if ($compressedSize === false || $compressedSize >= $originalSize) {
            @unlink($outputPath);
            copy($filePath, $outputPath = $this->generateTempPath($this->getExtension($filePath)));
            $compressedSize = $originalSize;
        }

        $outputMime = $convertTo
            ? $this->mimeFromFormat($convertTo)
            : $mimeType;

        return new CompressionResult(
            path: $outputPath,
            originalSize: $originalSize,
            compressedSize: $compressedSize,
            mimeType: $outputMime,
            wasCompressed: $compressedSize < $originalSize,
        );
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->config['supported_mimes'], true);
    }

    private function resolveDriver(): GdDriver|ImagickDriver
    {
        return match ($this->config['driver'] ?? 'gd') {
            'imagick' => new ImagickDriver(),
            default => new GdDriver(),
        };
    }

    private function resolveEncoder(?string $convertTo, string $mimeType, int $quality): AutoEncoder|JpegEncoder|PngEncoder|WebpEncoder|GifEncoder
    {
        $format = $convertTo ?? $this->formatFromMime($mimeType);

        return match ($format) {
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality),
            'png' => new PngEncoder(),
            'webp' => new WebpEncoder(quality: $quality),
            'gif' => new GifEncoder(),
            default => new AutoEncoder(quality: $quality),
        };
    }

    private function formatFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function mimeFromFormat(string $format): string
    {
        return match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private function resolveExtension(?string $convertTo, string $filePath): string
    {
        if ($convertTo !== null) {
            return $convertTo === 'jpeg' ? 'jpg' : $convertTo;
        }

        return $this->getExtension($filePath);
    }

    private function getExtension(string $filePath): string
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) ?: 'jpg';
    }

    private function generateTempPath(string $extension): string
    {
        $tempDir = $this->config['temp_dir'] ?? sys_get_temp_dir();

        return $tempDir . DIRECTORY_SEPARATOR . 'fc_' . uniqid() . '.' . $extension;
    }
}
