# Laravel File Compressor

Automatically compress images, PDFs and videos on upload in Laravel. Auto-detects the MIME type and applies the right compressor — zero config needed.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- GD extension (default) or Imagick
- **Optional:** Ghostscript (`gs`) for PDF compression
- **Optional:** FFmpeg (`ffmpeg`) for video compression

## Installation

```bash
composer require justsmiletoo/laravel-file-compressor
```

The service provider is auto-discovered. To publish the config file:

```bash
php artisan vendor:publish --tag=file-compressor-config
```

### System binaries (optional)

For PDF compression, install Ghostscript:

```bash
# Alpine (Docker)
apk add ghostscript

# Ubuntu/Debian
apt-get install ghostscript

# macOS
brew install ghostscript
```

For video compression, install FFmpeg:

```bash
# Alpine (Docker)
apk add ffmpeg

# Ubuntu/Debian
apt-get install ffmpeg

# macOS
brew install ffmpeg
```

## Usage

### Auto-detect and compress

The `compress()` method detects the file type and delegates to the right compressor:

```php
use JustSmileToo\FileCompressor\FileCompressor;

// From an UploadedFile (typical controller usage)
$result = app(FileCompressor::class)->compress($request->file('avatar'));

// From a file path
$result = app(FileCompressor::class)->compress('/path/to/file.jpg');

// Using the facade
use JustSmileToo\FileCompressor\Facades\FileCompressor;

$result = FileCompressor::compress($uploadedFile);
```

### Type-specific methods

Skip MIME detection and call a compressor directly:

```php
$result = FileCompressor::compressImage($file);
$result = FileCompressor::compressPdf($file);
$result = FileCompressor::compressVideo($file);
```

### Per-call options

Override config values for a single call:

```php
// Smaller avatar
$result = FileCompressor::compressImage($file, [
    'max_width' => 400,
    'max_height' => 400,
    'quality' => 70,
]);

// High-quality PDF
$result = FileCompressor::compressPdf($file, [
    'quality' => 'printer', // screen, ebook, printer, prepress
]);

// Lower quality video for previews
$result = FileCompressor::compressVideo($file, [
    'crf' => 32,
    'max_width' => 720,
    'preset' => 'fast',
]);
```

### CompressionResult

Every compression call returns a `CompressionResult` DTO:

```php
$result = FileCompressor::compress($file);

$result->path;              // string — path to the compressed file
$result->originalSize;      // int — original file size in bytes
$result->compressedSize;    // int — compressed file size in bytes
$result->mimeType;          // string — MIME type of the output
$result->wasCompressed;     // bool — false if compression was skipped or didn't reduce size
$result->savedBytes();      // int — bytes saved
$result->savedPercentage(); // float — e.g. 65.42
```

### Integration example

Typical usage in a file upload service:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JustSmileToo\FileCompressor\FileCompressor;

class FileUploadService
{
    public function __construct(
        private FileCompressor $compressor,
    ) {}

    public function upload(UploadedFile $file, string $directory): string
    {
        $result = $this->compressor->compress($file);

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        if ($result->wasCompressed) {
            return Storage::disk('public')->putFileAs(
                $directory,
                new \Illuminate\Http\File($result->path),
                $filename,
            );
        }

        return $file->storeAs($directory, $filename, 'public');
    }
}
```

### Check support

```php
FileCompressor::isSupported('image/jpeg'); // true
FileCompressor::isSupported('text/plain'); // false
FileCompressor::isEnabled();               // true (unless disabled in config)
```

## Configuration

| Key | Default | Description |
|-----|---------|-------------|
| `enabled` | `true` | Global toggle. Set to `false` to skip all compression. |
| `temp_dir` | `sys_get_temp_dir()` | Temporary directory for processing. |
| **Image** | | |
| `image.driver` | `gd` | Image driver: `gd` or `imagick`. |
| `image.quality` | `80` | JPEG/WebP quality (1-100). |
| `image.max_width` | `1920` | Max width in pixels. Aspect ratio preserved. |
| `image.max_height` | `1080` | Max height in pixels. Aspect ratio preserved. |
| `image.convert_to` | `null` | Convert format: `null` (keep original), `webp`, `jpg`, `png`. |
| **PDF** | | |
| `pdf.binary` | `gs` | Path to the Ghostscript binary. |
| `pdf.quality` | `ebook` | Preset: `screen` (72dpi), `ebook` (150dpi), `printer` (300dpi), `prepress`. |
| `pdf.timeout` | `60` | Process timeout in seconds. |
| **Video** | | |
| `video.binary` | `ffmpeg` | Path to the FFmpeg binary. |
| `video.codec` | `libx264` | Video codec. |
| `video.crf` | `28` | Constant Rate Factor (0-51). Lower = better quality, larger file. |
| `video.preset` | `medium` | Encoding speed preset. Slower = better compression. |
| `video.max_width` | `1280` | Max width in pixels (never upscales). |
| `video.audio_bitrate` | `128k` | Audio bitrate. |
| `video.timeout` | `300` | Process timeout in seconds. |

All values can be overridden via environment variables. See `config/file-compressor.php` for the full list.

## Handling unsupported files

If a file type is not supported, `compress()` throws `UnsupportedFileTypeException`. You can check support beforehand:

```php
use JustSmileToo\FileCompressor\Exceptions\UnsupportedFileTypeException;

if (FileCompressor::isSupported($file->getMimeType())) {
    $result = FileCompressor::compress($file);
} else {
    // Store without compression
}
```

Or catch the exception:

```php
try {
    $result = FileCompressor::compress($file);
} catch (UnsupportedFileTypeException) {
    // File type not supported, store as-is
}
```

## Temp file cleanup

Compressed files are written to the configured `temp_dir`. After storing the compressed file to your disk, the temp file can be safely deleted. The OS will also clean up temp files periodically.

## Testing

```bash
composer test
```

Place test fixtures (`sample.jpg`, `sample.pdf`, `sample.mp4`) in `tests/Fixtures/` to enable all tests.

## License

MIT. See [LICENSE](LICENSE).
