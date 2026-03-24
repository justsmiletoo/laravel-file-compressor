<?php

declare(strict_types=1);

use JustSmileToo\FileCompressor\FileCompressor;
use JustSmileToo\FileCompressor\Exceptions\UnsupportedFileTypeException;

it('resolves from the container', function () {
    $compressor = app(FileCompressor::class);

    expect($compressor)->toBeInstanceOf(FileCompressor::class);
});

it('reports supported MIME types correctly', function () {
    $compressor = app(FileCompressor::class);

    expect($compressor->isSupported('image/jpeg'))->toBeTrue()
        ->and($compressor->isSupported('image/png'))->toBeTrue()
        ->and($compressor->isSupported('image/webp'))->toBeTrue()
        ->and($compressor->isSupported('application/pdf'))->toBeTrue()
        ->and($compressor->isSupported('video/mp4'))->toBeTrue()
        ->and($compressor->isSupported('text/plain'))->toBeFalse()
        ->and($compressor->isSupported('application/json'))->toBeFalse();
});

it('returns noop result when compression is disabled', function () {
    config()->set('file-compressor.enabled', false);

    $compressor = app(FileCompressor::class);
    $filePath = $this->fixturePath('sample.jpg');

    $result = $compressor->compress($filePath);

    expect($result->wasCompressed)->toBeFalse()
        ->and($result->path)->toBe($filePath)
        ->and($result->originalSize)->toBe($result->compressedSize);
});

it('throws exception for unsupported file type', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'fc_test_');
    file_put_contents($tempFile, 'plain text content');

    try {
        app(FileCompressor::class)->compress($tempFile);
    } finally {
        @unlink($tempFile);
    }
})->throws(UnsupportedFileTypeException::class);

it('is enabled by default', function () {
    expect(app(FileCompressor::class)->isEnabled())->toBeTrue();
});
