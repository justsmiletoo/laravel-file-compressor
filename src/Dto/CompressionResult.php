<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Dto;

final readonly class CompressionResult
{
    public function __construct(
        public string $path,
        public int $originalSize,
        public int $compressedSize,
        public string $mimeType,
        public bool $wasCompressed,
    ) {}

    public function savedBytes(): int
    {
        return $this->originalSize - $this->compressedSize;
    }

    public function savedPercentage(): float
    {
        if ($this->originalSize === 0) {
            return 0.0;
        }

        return round(($this->savedBytes() / $this->originalSize) * 100, 2);
    }
}
