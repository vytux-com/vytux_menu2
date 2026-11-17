<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vytux\WebtreesModules\VytuxMenu2\IconValidator;
use Vytux\WebtreesModules\VytuxMenu2\InvalidIconException;

use function base64_decode;
use function pack;
use function str_repeat;

final class IconValidatorTest extends TestCase
{
    public const string PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public const string GIF_1X1 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public static function pngHeader(int $width, int $height): string
    {
        return "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . pack('NN', $width, $height) . "\x08\x06\x00\x00\x00" . str_repeat("\x00", 4);
    }

    public function testAcceptsPng(): void
    {
        self::assertSame('image/png', IconValidator::validate((string) base64_decode(self::PNG_1X1, true)));
    }

    public function testAcceptsGif(): void
    {
        self::assertSame('image/gif', IconValidator::validate((string) base64_decode(self::GIF_1X1, true)));
    }

    /**
     * @return array<string,array{string,int}>
     */
    public static function invalidProvider(): array
    {
        return [
            'empty'     => ['', InvalidIconException::REASON_EMPTY],
            'too large' => [self::pngHeader(1, 1) . str_repeat("\x00", IconValidator::MAX_BYTES), InvalidIconException::REASON_TOO_LARGE],
            'text'      => ['hello world', InvalidIconException::REASON_NOT_AN_IMAGE],
            'svg'       => ['<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><script>alert(1)</script></svg>', InvalidIconException::REASON_NOT_AN_IMAGE],
            'html'      => ['<html><body onload="alert(1)"></body></html>', InvalidIconException::REASON_NOT_AN_IMAGE],
            'bmp'       => ['BM' . pack('V', 58) . "\x00\x00\x00\x00" . pack('V', 54) . pack('V', 40) . pack('VV', 1, 1) . pack('vv', 1, 24) . str_repeat("\x00", 28), InvalidIconException::REASON_NOT_AN_IMAGE],
            'too wide'  => [self::pngHeader(IconValidator::MAX_DIMENSION + 1, 10), InvalidIconException::REASON_DIMENSIONS],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidUploads(string $bytes, int $reason): void
    {
        $this->expectException(InvalidIconException::class);
        $this->expectExceptionCode($reason);

        IconValidator::validate($bytes);
    }

    public function testMimeTypeAllowList(): void
    {
        self::assertTrue(IconValidator::isAllowedMimeType('image/webp'));
        self::assertFalse(IconValidator::isAllowedMimeType('image/svg+xml'));
        self::assertFalse(IconValidator::isAllowedMimeType('text/html'));
    }
}
