<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2\Tests;

use PHPUnit\Framework\TestCase;
use Vytux\WebtreesModules\VytuxMenu2\IconSet;

final class IconSetTest extends TestCase
{
    public function testRoundTripThroughJson(): void
    {
        $icons = IconSet::empty()
            ->with('webtrees', 'image/png', "\x89PNG-webtrees")
            ->with(IconSet::DEFAULT_KEY, 'image/gif', 'GIF89a-default');

        $copy = IconSet::fromJson($icons->toJson());

        self::assertSame([IconSet::DEFAULT_KEY, 'webtrees'], $copy->keys());
        self::assertSame(['mime' => 'image/png', 'bytes' => "\x89PNG-webtrees"], $copy->get('webtrees'));
        self::assertSame(['mime' => 'image/gif', 'bytes' => 'GIF89a-default'], $copy->get(IconSet::DEFAULT_KEY));
    }

    public function testEmptySetIsStoredAsEmptyString(): void
    {
        $icons = IconSet::empty()->with('xenea', 'image/png', 'x')->without('xenea');

        self::assertTrue($icons->isEmpty());
        self::assertSame('', $icons->toJson());
        self::assertTrue(IconSet::fromJson('')->isEmpty());
        self::assertNull($icons->get('xenea'));
    }

    public function testInvalidEntriesAreDiscarded(): void
    {
        $json = '{"*":{"mime":"image/svg+xml","data":"PHN2Zz4="},"../x":{"mime":"image/png","data":"eA=="},'
            . '"clouds":{"mime":"image/png"},"colors":"nonsense","xenea":{"mime":"image/webp","data":"eA=="}}';

        $icons = IconSet::fromJson($json);

        self::assertSame(['xenea'], $icons->keys());
        self::assertTrue(IconSet::fromJson('not json')->isEmpty());
    }

    public function testValidKeys(): void
    {
        self::assertTrue(IconSet::isValidKey(IconSet::DEFAULT_KEY));
        self::assertTrue(IconSet::isValidKey('_jc-theme-justlight_'));
        self::assertFalse(IconSet::isValidKey(''));
        self::assertFalse(IconSet::isValidKey('a b'));
        self::assertFalse(IconSet::isValidKey('x"y'));
    }
}
