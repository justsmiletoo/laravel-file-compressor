<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Facades;

use Illuminate\Support\Facades\Facade;
use JustSmileToo\FileCompressor\Dto\CompressionResult;
use JustSmileToo\FileCompressor\Dto\UploadResult;

/**
 * @method static CompressionResult compress(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressImage(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressPdf(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static CompressionResult compressVideo(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, array $options = [])
 * @method static UploadResult upload(\Illuminate\Http\UploadedFile|\SplFileInfo|string $file, string $folder, array $options = [])
 * @method static bool delete(?string $path)
 * @method static string|null url(?string $path)
 * @method static bool exists(?string $path)
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
