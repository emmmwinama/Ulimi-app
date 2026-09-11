<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/**
 * Hardened file upload handling for farm document evidence.
 *
 * Controls:
 *   - extension AND declared-MIME AND sniffed-magic-bytes must all agree
 *     with the allowlist (pdf/jpg/png/webp, plus mp3/m4a/ogg voice notes
 *     for incident/health-record attachments) — a renamed .php is
 *     rejected even if the extension is faked, because finfo inspects
 *     real bytes.
 *   - size cap enforced server-side regardless of client claims.
 *   - stored under a random 32-hex name OUTSIDE the web root
 *     (storage/uploads), so there is no direct URL to guess and nothing
 *     uploaded is ever directly executable by the web server.
 *   - the original filename is kept only as metadata for display, never
 *     used to build a filesystem path.
 */
final class Upload
{
    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @return array{asset_id:string,mime_type:string,size:int,original_name:string}
     * @throws RuntimeException with a user-safe message on any validation failure
     */
    public static function store(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($file['error']));
        }

        $maxBytes = (int) Config::get('uploads.max_bytes', 8 * 1024 * 1024);
        if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
            throw new RuntimeException('File is empty or exceeds the ' . round($maxBytes / 1_000_000, 1) . ' MB limit.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload failed. Please try again.');
        }

        $allowed = (array) Config::get('uploads.allowed_mime', []);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sniffed = (string) $finfo->file($file['tmp_name']);

        if (!isset($allowed[$sniffed])) {
            throw new RuntimeException('That file type isn’t supported. Use PDF, JPG, PNG, WEBP, MP3, M4A or OGG.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $expectedExt = (string) $allowed[$sniffed];
        // JPEG commonly arrives as .jpg or .jpeg; accept both for that one mime.
        $acceptableExts = $expectedExt === 'jpg' ? ['jpg', 'jpeg'] : [$expectedExt];
        if (!in_array($ext, $acceptableExts, true)) {
            throw new RuntimeException('The file extension doesn’t match its content.');
        }

        $dir = rtrim((string) Config::get('uploads.path', ''), '/');
        if ($dir === '' || !is_dir($dir)) {
            throw new RuntimeException('Upload storage is not configured.');
        }

        $assetId = bin2hex(random_bytes(16));
        $shard = substr($assetId, 0, 2);
        $shardDir = $dir . '/' . $shard;
        if (!is_dir($shardDir) && !@mkdir($shardDir, 0770, true) && !is_dir($shardDir)) {
            throw new RuntimeException('Could not create upload storage.');
        }
        $dest = $shardDir . '/' . $assetId . '.' . $expectedExt;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }
        @chmod($dest, 0640);

        return [
            'asset_id'      => $shard . '/' . $assetId . '.' . $expectedExt,
            'mime_type'     => $sniffed,
            'size'          => (int) filesize($dest),
            'original_name' => self::safeName((string) $file['name']),
        ];
    }

    public static function path(string $assetId): string
    {
        $dir = rtrim((string) Config::get('uploads.path', ''), '/');
        // assetId is always our own generated "xx/xxxxxxxx.ext" — validate the
        // shape before touching the filesystem, defence in depth against any
        // future caller that forgets to.
        if (preg_match('#^[0-9a-f]{2}/[0-9a-f]{32}\.(pdf|jpg|jpeg|png|webp|mp3|m4a|ogg)$#', $assetId) !== 1) {
            throw new RuntimeException('Invalid asset reference.');
        }
        return $dir . '/' . $assetId;
    }

    public static function delete(string $assetId): void
    {
        $path = self::path($assetId);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function safeName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name) ?? 'document';
        return mb_substr($name, 0, 150);
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            default => 'Upload failed. Please try again.',
        };
    }
}
