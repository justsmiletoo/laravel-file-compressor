<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Exceptions;

class BinaryNotFoundException extends CompressionException
{
    public static function make(string $binary): self
    {
        return new self("Binary '{$binary}' not found on the system. Please install it or update the path in config.");
    }
}
