<?php

declare(strict_types=1);

use JustSmileToo\FileCompressor\Compressors\ImageCompressor;

beforeEach(function () {
    $this->compressor = app(ImageCompressor::class);
});

it('supports image MIME types', function () {
    expect($this->compressor->supports('image/jpeg'))->toBeTrue()
        ->and($this->compressor->supports('image/png'))->toBeTrue()
        ->and($this->compressor->supports('image/gif'))->toBeTrue()
        ->and($this->compressor->supports('image/webp'))->toBeTrue()
        ->and($this->compressor->supports('image/svg+xml'))->toBeFalse()
        ->and($this->compressor->supports('application/pdf'))->toBeFalse();
});

it('compresses a JPEG image', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $result = $this->compressor->compress($fixturePath);

    expect($result->mimeType)->toBe('image/jpeg')
        ->and($result->originalSize)->toBeGreaterThan(0)
        ->and($result->compressedSize)->toBeGreaterThan(0)
        ->and(file_exists($result->path))->toBeTrue();

    @unlink($result->path);
});

it('respects quality option override', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $highQuality = $this->compressor->compress($fixturePath, ['quality' => 95]);
    $lowQuality = $this->compressor->compress($fixturePath, ['quality' => 30]);

    expect($lowQuality->compressedSize)->toBeLessThanOrEqual($highQuality->compressedSize);

    @unlink($highQuality->path);
    @unlink($lowQuality->path);
});

it('respects max dimension options', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $result = $this->compressor->compress($fixturePath, [
        'max_width' => 200,
        'max_height' => 200,
    ]);

    expect(file_exists($result->path))->toBeTrue()
        ->and($result->compressedSize)->toBeGreaterThan(0);

    @unlink($result->path);
});

it('applies cover mode for cropping', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $result = $this->compressor->compress($fixturePath, [
        'max_width' => 200,
        'max_height' => 200,
        'mode' => 'cover',
    ]);

    expect(file_exists($result->path))->toBeTrue()
        ->and($result->compressedSize)->toBeGreaterThan(0);

    $info = getimagesize($result->path);
    expect($info[0])->toBe(200)
        ->and($info[1])->toBe(200);

    @unlink($result->path);
});

it('applies preset options', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $result = $this->compressor->compress($fixturePath, ['preset' => 'avatar']);

    expect(file_exists($result->path))->toBeTrue();

    $info = getimagesize($result->path);
    expect($info[0])->toBe(200)
        ->and($info[1])->toBe(200);

    @unlink($result->path);
});

it('allows preset overrides', function () {
    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $result = $this->compressor->compress($fixturePath, [
        'preset' => 'avatar',
        'max_width' => 100,
        'max_height' => 100,
    ]);

    expect(file_exists($result->path))->toBeTrue();

    $info = getimagesize($result->path);
    expect($info[0])->toBe(100)
        ->and($info[1])->toBe(100);

    @unlink($result->path);
});
