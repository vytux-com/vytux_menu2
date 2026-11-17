<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use function getimagesizefromstring;
use function in_array;
use function strlen;

/**
 * Checks that uploaded bytes are a small raster image that is safe to serve back to browsers.
 *
 * SVG is deliberately not accepted, as it can contain scripts.
 */
final class IconValidator
{
    public const int MAX_BYTES = 102400;

    public const int MAX_DIMENSION = 512;

    /** @var array<int,string> PHP image type => MIME type */
    public const array MIME_TYPES = [
        IMAGETYPE_PNG  => 'image/png',
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_GIF  => 'image/gif',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    /**
     * @return string The detected MIME type
     *
     * @throws InvalidIconException
     */
    public static function validate(string $bytes): string
    {
        if ($bytes === '') {
            throw new InvalidIconException('Empty file', InvalidIconException::REASON_EMPTY);
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidIconException('File too large', InvalidIconException::REASON_TOO_LARGE);
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false || !isset(self::MIME_TYPES[$info[2]])) {
            throw new InvalidIconException('Not a supported image', InvalidIconException::REASON_NOT_AN_IMAGE);
        }

        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new InvalidIconException('Bad dimensions', InvalidIconException::REASON_DIMENSIONS);
        }

        return self::MIME_TYPES[$info[2]];
    }

    public static function isAllowedMimeType(string $mime): bool
    {
        return in_array($mime, self::MIME_TYPES, true);
    }
}
