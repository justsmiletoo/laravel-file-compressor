<?php

declare(strict_types=1);

namespace JustSmileToo\FileCompressor;

use Illuminate\Support\ServiceProvider;
use JustSmileToo\FileCompressor\Compressors\ImageCompressor;
use JustSmileToo\FileCompressor\Compressors\PdfCompressor;
use JustSmileToo\FileCompressor\Compressors\VideoCompressor;

class FileCompressorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/file-compressor.php', 'file-compressor');

        $this->app->singleton(ImageCompressor::class, function ($app) {
            $config = $app['config']->get('file-compressor.image');
            $config['temp_dir'] = $app['config']->get('file-compressor.temp_dir', sys_get_temp_dir());

            return new ImageCompressor($config);
        });

        $this->app->singleton(PdfCompressor::class, function ($app) {
            $config = $app['config']->get('file-compressor.pdf');
            $config['temp_dir'] = $app['config']->get('file-compressor.temp_dir', sys_get_temp_dir());

            return new PdfCompressor($config);
        });

        $this->app->singleton(VideoCompressor::class, function ($app) {
            $config = $app['config']->get('file-compressor.video');
            $config['temp_dir'] = $app['config']->get('file-compressor.temp_dir', sys_get_temp_dir());

            return new VideoCompressor($config);
        });

        $this->app->singleton(FileCompressor::class, function ($app) {
            return new FileCompressor(
                $app->make(ImageCompressor::class),
                $app->make(PdfCompressor::class),
                $app->make(VideoCompressor::class),
                $app['config']->get('file-compressor'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/file-compressor.php' => config_path('file-compressor.php'),
        ], 'file-compressor-config');
    }
}
