<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Facades;

use Illuminate\Support\Facades\Facade;
use JustSmileToo\FileCompressor\Dto\CompressionResult;

/**
 * @method static CompressionResult compress(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressImage(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressPdf(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressVideo(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static bool isSupported(string $mimeType)
 * @method static bool isEnabled()
 *
 * @see \JustSmileToo\FileCompressor\FileCompressor
 */
class FileCompressor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JustSmileToo\FileCompressor\FileCompressor::class;
    }
}
