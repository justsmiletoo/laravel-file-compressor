<?php

declare(strict_types=1);

use JustSmileToo\FileCompressor\Compressors\PdfCompressor;
use JustSmileToo\FileCompressor\Exceptions\BinaryNotFoundException;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;

beforeEach(function () {
    $this->compressor = app(PdfCompressor::class);
});

it('supports PDF MIME type', function () {
    expect($this->compressor->supports('application/pdf'))->toBeTrue()
        ->and($this->compressor->supports('image/jpeg'))->toBeFalse();
});

it('throws exception for invalid quality preset', function () {
    $fixturePath = $this->fixturePath('sample.pdf');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.pdf not found.');
    }

    $this->compressor->compress($fixturePath, ['quality' => 'invalid']);
})->throws(CompressionException::class, 'Invalid PDF quality preset');

it('throws exception when ghostscript is not installed', function () {
    $config = config('file-compressor.pdf');
    $config['binary'] = 'nonexistent-gs-binary';
    $config['temp_dir'] = sys_get_temp_dir();

    $compressor = new PdfCompressor($config);

    $tempFile = tempnam(sys_get_temp_dir(), 'fc_test_');
    file_put_contents($tempFile, '%PDF-1.4 fake pdf content');

    try {
        $compressor->compress($tempFile);
    } finally {
        @unlink($tempFile);
    }
})->throws(BinaryNotFoundException::class);

it('compresses a PDF file', function () {
    $fixturePath = $this->fixturePath('sample.pdf');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.pdf not found.');
    }

    try {
        $result = $this->compressor->compress($fixturePath);
    } catch (BinaryNotFoundException) {
        $this->markTestSkipped('Ghostscript is not installed.');
    }

    expect($result->mimeType)->toBe('application/pdf')
        ->and($result->originalSize)->toBeGreaterThan(0)
        ->and(file_exists($result->path))->toBeTrue();

    @unlink($result->path);
});
