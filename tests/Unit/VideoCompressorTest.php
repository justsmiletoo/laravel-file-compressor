<?php

declare(strict_types=1);

use JustSmileToo\FileCompressor\Compressors\VideoCompressor;
use JustSmileToo\FileCompressor\Exceptions\BinaryNotFoundException;
use JustSmileToo\FileCompressor\Exceptions\CompressionException;

beforeEach(function () {
    $this->compressor = app(VideoCompressor::class);
});

it('supports video MIME types', function () {
    expect($this->compressor->supports('video/mp4'))->toBeTrue()
        ->and($this->compressor->supports('video/webm'))->toBeTrue()
        ->and($this->compressor->supports('video/quicktime'))->toBeTrue()
        ->and($this->compressor->supports('image/jpeg'))->toBeFalse();
});

it('throws exception for invalid preset', function () {
    $fixturePath = $this->fixturePath('sample.mp4');

    if (! file_exists($fixturePath)) {
        $this->markTestSkipped('Test fixture sample.mp4 not found.');
    }

    $this->compressor->compress($fixturePath, ['preset' => 'invalid']);
})->throws(CompressionException::class, 'Invalid video preset');

it('throws exception when ffmpeg is not installed', function () {
    $config = config('file-compressor.video');
    $config['binary'] = 'nonexistent-ffmpeg-binary';
    $config['temp_dir'] = sys_get_temp_dir();

    $compressor = new VideoCompressor($config);

    $tempFile = tempnam(sys_get_temp_dir(), 'fc_test_');
    file_put_contents($tempFile, 'fake video content');

    try {
        $compressor->compress($tempFile);
    } finally {
        @unlink($tempFile);
    }
})->throws(BinaryNotFoundException::class);
