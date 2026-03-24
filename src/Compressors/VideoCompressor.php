<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Compressors;

use JustSmileToo\FileCompressor\Dto\CompressionResult;
use JustSmileToo\FileCompressor\Exceptions\BinaryNotFoundException;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class VideoCompressor implements CompressorInterface
{
    private const VALID_PRESETS = [
        'ultrafast', 'superfast', 'veryfast', 'faster', 'fast',
        'medium', 'slow', 'slower', 'veryslow',
    ];

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
        $binary = $this->resolveBinary();
        $originalSize = filesize($filePath);

        if ($originalSize === false) {
            throw new CompressionException("Unable to read file: {$filePath}");
        }

        $mimeType = mime_content_type($filePath);

        if ($mimeType === false) {
            $mimeType = 'video/mp4';
        }

        $codec = $options['codec'] ?? $this->config['codec'];
        $crf = $options['crf'] ?? $this->config['crf'];
        $preset = $options['preset'] ?? $this->config['preset'];
        $maxWidth = $options['max_width'] ?? $this->config['max_width'];
        $audioBitrate = $options['audio_bitrate'] ?? $this->config['audio_bitrate'];
        $timeout = $options['timeout'] ?? $this->config['timeout'];

        if (! in_array($preset, self::VALID_PRESETS, true)) {
            throw new CompressionException("Invalid video preset: {$preset}. Valid: " . implode(', ', self::VALID_PRESETS));
        }

        $outputPath = $this->generateTempPath();

        $process = new Process([
            $binary,
            '-i', $filePath,
            '-vcodec', $codec,
            '-crf', (string) $crf,
            '-preset', $preset,
            '-vf', "scale='min({$maxWidth},iw)':-2",
            '-acodec', 'aac',
            '-b:a', $audioBitrate,
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ]);

        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($outputPath);
            throw new CompressionException("FFmpeg compression failed: {$process->getErrorOutput()}");
        }

        $compressedSize = filesize($outputPath);

        if ($compressedSize === false || $compressedSize >= $originalSize) {
            @unlink($outputPath);
            copy($filePath, $outputPath = $this->generateTempPath());
            $compressedSize = $originalSize;
        }

        return new CompressionResult(
            path: $outputPath,
            originalSize: $originalSize,
            compressedSize: $compressedSize,
            mimeType: $mimeType,
            wasCompressed: $compressedSize < $originalSize,
        );
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->config['supported_mimes'], true);
    }

    private function resolveBinary(): string
    {
        $binary = $this->config['binary'];

        $finder = new ExecutableFinder();
        $resolved = $finder->find($binary);

        if ($resolved === null) {
            throw BinaryNotFoundException::make($binary);
        }

        return $resolved;
    }

    private function generateTempPath(): string
    {
        $tempDir = $this->config['temp_dir'] ?? sys_get_temp_dir();

        return $tempDir . DIRECTORY_SEPARATOR . 'fc_' . uniqid() . '.mp4';
    }
}
