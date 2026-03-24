<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor\Tests;

use JustSmileToo\FileCompressor\FileCompressorServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FileCompressorServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'FileCompressor' => \JustSmileToo\FileCompressor\Facades\FileCompressor::class,
        ];
    }

    protected function fixturePath(string $filename): string
    {
        return __DIR__ . '/Fixtures/' . $filename;
    }
}
