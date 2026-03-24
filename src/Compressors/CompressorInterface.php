<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Compressors;

use JustSmileToo\FileCompressor\Dto\CompressionResult;

interface CompressorInterface
{
    /**
     * Compress the given file and return the result.
     *
     * @param  array<string, mixed>  $options  Per-call overrides for the compressor config.
     */
    public function compress(string $filePath, array $options = []): CompressionResult;

    /**
     * Determine if this compressor supports the given MIME type.
     */
    public function supports(string $mimeType): bool;
}
