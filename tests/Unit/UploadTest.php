<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use JustSmileToo\FileCompressor\FileCompressor;

it('uploads and compresses a file', function () {
    Storage::fake('public');

    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $compressor = app(FileCompressor::class);
    $result = $compressor->upload($fixturePath, 'images');

    expect($result->path)->toStartWith('images/')
        ->and($result->originalSize)->toBeGreaterThan(0)
        ->and($result->compressedSize)->toBeGreaterThan(0)
        ->and(Storage::disk('public')->exists($result->path))->toBeTrue();
});

it('uploads with a preset', function () {
    Storage::fake('public');

    $fixturePath = $this->fixturePath('sample.jpg');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.jpg not found.');
    }

    $compressor = app(FileCompressor::class);
    $result = $compressor->upload($fixturePath, 'avatars', ['preset' => 'avatar']);

    expect($result->path)->toStartWith('avatars/')
        ->and(Storage::disk('public')->exists($result->path))->toBeTrue();
});

it('deletes a file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('test/file.jpg', 'content');

    $compressor = app(FileCompressor::class);

    expect($compressor->exists('test/file.jpg'))->toBeTrue();

    $compressor->delete('test/file.jpg');

    expect($compressor->exists('test/file.jpg'))->toBeFalse();
});

it('returns url for a file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('test/file.jpg', 'content');

    $compressor = app(FileCompressor::class);
    $url = $compressor->url('test/file.jpg');

    expect($url)->toContain('test/file.jpg');
});

it('handles null path gracefully', function () {
    $compressor = app(FileCompressor::class);

    expect($compressor->delete(null))->toBeFalse()
        ->and($compressor->url(null))->toBeNull()
        ->and($compressor->exists(null))->toBeFalse();
});

it('stores unsupported files without compression', function () {
    Storage::fake('public');

    $tempFile = tempnam(sys_get_temp_dir(), 'fc_test_');
    file_put_contents($tempFile, 'plain text content');

    $compressor = app(FileCompressor::class);
    $result = $compressor->upload($tempFile, 'documents');

    expect($result->path)->toStartWith('documents/')
        ->and($result->wasCompressed)->toBeFalse()
        ->and(Storage::disk('public')->exists($result->path))->toBeTrue();

    @unlink($tempFile);
});
