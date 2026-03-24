<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Compressors;

use JustSmileToo\FileCompressor\Dto\CompressionResult;
use JustSmileToo\FileCompressor\Exceptions\BinaryNotFoundException;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PdfCompressor implements CompressorInterface
{
    private const VALID_QUALITIES = ['screen', 'ebook', 'printer', 'prepress'];

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

        $quality = $options['quality'] ?? $this->config['quality'];
        $timeout = $options['timeout'] ?? $this->config['timeout'];

        if (! in_array($quality, self::VALID_QUALITIES, true)) {
            throw new CompressionException("Invalid PDF quality preset: {$quality}. Valid: " . implode(', ', self::VALID_QUALITIES));
        }

        $outputPath = $this->generateTempPath();

        $process = new Process([
            $binary,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            "-dPDFSETTINGS=/{$quality}",
            '-dNOPAUSE',
            '-dBATCH',
            '-dQUIET',
            "-sOutputFile={$outputPath}",
            $filePath,
        ]);

        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($outputPath);
            throw new CompressionException("Ghostscript compression failed: {$process->getErrorOutput()}");
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
            mimeType: 'application/pdf',
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

        return $tempDir . DIRECTORY_SEPARATOR . 'fc_' . uniqid() . '.pdf';
    }
}
