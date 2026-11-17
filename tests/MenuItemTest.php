<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2\Tests;

use PHPUnit\Framework\TestCase;
use Vytux\WebtreesModules\VytuxMenu2\MenuItem;

final class MenuItemTest extends TestCase
{
    private const int PRIV_PRIVATE = 2; // Visitors
    private const int PRIV_USER    = 1; // Members
    private const int PRIV_NONE    = 0; // Managers
    private const int PRIV_HIDE    = -1; // Nobody

    public function testAccessLevels(): void
    {
        $public  = new MenuItem(1, null, 1, 'Public', '/', self::PRIV_PRIVATE);
        $members = new MenuItem(2, null, 2, 'Members', '/', self::PRIV_USER);
        $hidden  = new MenuItem(3, null, 3, 'Hidden', '/', self::PRIV_HIDE);

        self::assertTrue($public->isVisibleTo('en-US', self::PRIV_PRIVATE));
        self::assertFalse($members->isVisibleTo('en-US', self::PRIV_PRIVATE));
        self::assertTrue($members->isVisibleTo('en-US', self::PRIV_USER));
        self::assertTrue($members->isVisibleTo('en-US', self::PRIV_NONE));
        self::assertFalse($hidden->isVisibleTo('en-US', self::PRIV_NONE));
    }

    public function testLanguages(): void
    {
        $all        = new MenuItem(1, null, 1, 'All', '/', self::PRIV_PRIVATE);
        $lithuanian = new MenuItem(2, null, 2, 'LT', '/', self::PRIV_PRIVATE, ['lt', 'en-GB']);

        self::assertTrue($all->isVisibleTo('fr', self::PRIV_PRIVATE));
        self::assertTrue($lithuanian->isVisibleTo('lt', self::PRIV_PRIVATE));
        self::assertTrue($lithuanian->isVisibleTo('en-GB', self::PRIV_PRIVATE));
        self::assertFalse($lithuanian->isVisibleTo('en-US', self::PRIV_PRIVATE));
    }
}
