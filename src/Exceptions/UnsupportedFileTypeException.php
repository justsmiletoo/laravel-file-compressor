<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Exceptions;

class UnsupportedFileTypeException extends CompressionException
{
    public static function make(string $mimeType): self
    {
        return new self("File type '{$mimeType}' is not supported for compression.");
    }
}
