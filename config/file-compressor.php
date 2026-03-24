<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Toggle
    |--------------------------------------------------------------------------
    |
    | Set to false to disable all compression. The compress() method will
    | return a no-op result with the original file path and size.
    |
    */

    'enabled' => env('FILE_COMPRESSOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | Directory used to store compressed files during processing. The caller
    | is responsible for moving or cleaning up the compressed file after use.
    |
    */

    'temp_dir' => env('FILE_COMPRESSOR_TEMP_DIR', sys_get_temp_dir()),

    /*
    |--------------------------------------------------------------------------
    | Image Compression
    |--------------------------------------------------------------------------
    |
    | Uses intervention/image v3. Supported drivers: 'gd' (default) or 'imagick'.
    | Images are scaled down proportionally if they exceed max dimensions.
    | The original format is preserved by default.
    |
    */

    'image' => [
        'driver' => env('FILE_COMPRESSOR_IMAGE_DRIVER', 'gd'),
        'quality' => (int) env('FILE_COMPRESSOR_IMAGE_QUALITY', 80),
        'max_width' => (int) env('FILE_COMPRESSOR_IMAGE_MAX_WIDTH', 1920),
        'max_height' => (int) env('FILE_COMPRESSOR_IMAGE_MAX_HEIGHT', 1080),
        'mode' => 'scale', // 'scale' (proportional) or 'cover' (crop to fill)
        'convert_to' => env('FILE_COMPRESSOR_IMAGE_CONVERT_TO'), // null, 'webp', 'jpg', 'png'
        'supported_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],

        /*
        |--------------------------------------------------------------------------
        | Image Presets
        |--------------------------------------------------------------------------
        |
        | Named presets for common use cases. Use via options: ['preset' => 'avatar'].
        | Preset values override the defaults above. Any option not set in the
        | preset falls back to the default config.
        |
        */

        'presets' => [
            'avatar' => ['max_width' => 200, 'max_height' => 200, 'mode' => 'cover', 'quality' => 80],
            'thumbnail' => ['max_width' => 300, 'max_height' => 300, 'mode' => 'cover', 'quality' => 80],
            'banner' => ['max_width' => 1920, 'max_height' => 600, 'mode' => 'scale', 'quality' => 85],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Compression
    |--------------------------------------------------------------------------
    |
    | Uses Ghostscript. The binary must be installed on the system.
    | Quality presets: 'screen' (72dpi), 'ebook' (150dpi), 'printer' (300dpi), 'prepress' (300dpi+).
    |
    */

    'pdf' => [
        'binary' => env('FILE_COMPRESSOR_GS_BINARY', 'gs'),
        'quality' => env('FILE_COMPRESSOR_PDF_QUALITY', 'ebook'),
        'timeout' => (int) env('FILE_COMPRESSOR_PDF_TIMEOUT', 60),
        'supported_mimes' => [
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Compression
    |--------------------------------------------------------------------------
    |
    | Uses FFmpeg. The binary must be installed on the system.
    | CRF range: 0 (lossless) to 51 (worst). 23 is default, 28 is good for uploads.
    | Preset: ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow.
    |
    */

    'video' => [
        'binary' => env('FILE_COMPRESSOR_FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FILE_COMPRESSOR_FFPROBE_BINARY', 'ffprobe'),
        'codec' => env('FILE_COMPRESSOR_VIDEO_CODEC', 'libx264'),
        'crf' => (int) env('FILE_COMPRESSOR_VIDEO_CRF', 28),
        'preset' => env('FILE_COMPRESSOR_VIDEO_PRESET', 'medium'),
        'max_width' => (int) env('FILE_COMPRESSOR_VIDEO_MAX_WIDTH', 1280),
        'audio_bitrate' => env('FILE_COMPRESSOR_VIDEO_AUDIO_BITRATE', '128k'),
        'timeout' => (int) env('FILE_COMPRESSOR_VIDEO_TIMEOUT', 300),
        'supported_mimes' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/webm',
        ],
    ],

];
