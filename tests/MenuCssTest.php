<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2\Tests;

use PHPUnit\Framework\TestCase;
use Vytux\WebtreesModules\VytuxMenu2\IconSet;
use Vytux\WebtreesModules\VytuxMenu2\MenuCss;

use function strpos;

final class MenuCssTest extends TestCase
{
    private MenuCss $css;

    protected function setUp(): void
    {
        $this->css = new MenuCss('menu-x');
    }

    public function testNothingConfiguredGivesNoCss(): void
    {
        self::assertSame('', $this->css->build([], [], "  \n"));
    }

    public function testSelectorsMatchCoreThemeConventions(): void
    {
        $css = $this->css->build(
            [MenuCss::TARGET_MAIN => ['webtrees' => '/main.png'], MenuCss::TARGET_ITEM => ['xenea' => '/item.png']],
            [MenuCss::TARGET_EDIT => ['fab' => '/edit.png'], 7 => ['clouds' => '/seven.png']],
        );

        self::assertStringContainsString('.wt-theme-webtrees .menu-x>.nav-link::before{content:"";', $css);
        self::assertStringContainsString('.wt-theme-xenea .menu-x-item::before{', $css);
        self::assertStringContainsString('.wt-theme-fab .menu-x-edit::before{', $css);
        self::assertStringContainsString('.wt-theme-clouds .menu-x-item-7::before{', $css);
        self::assertStringContainsString('url("/seven.png")', $css);
        self::assertStringContainsString('.wt-theme-webtrees .menu-x{--menu-x-size:50px;', $css);
    }

    public function testDefaultUploadIsOnlyUsedByIconThemes(): void
    {
        $css = $this->css->build([], [3 => [IconSet::DEFAULT_KEY => '/three.png']]);

        self::assertStringContainsString(':is(.wt-theme-clouds,.wt-theme-colors,.wt-theme-webtrees,.wt-theme-xenea) .menu-x-item-3::before{', $css);
        self::assertStringNotContainsString('wt-theme-fab', $css);
    }

    public function testPrecedence(): void
    {
        $css = $this->css->build(
            [MenuCss::TARGET_ITEM => ['webtrees' => '/theme-default.png']],
            [5 => ['webtrees' => '/theme-upload.png', IconSet::DEFAULT_KEY => '/generic-upload.png']],
        );

        $default = strpos($css, '/theme-default.png');
        $generic = strpos($css, '/generic-upload.png');
        $theme   = strpos($css, '/theme-upload.png');

        self::assertIsInt($default);
        self::assertIsInt($generic);
        self::assertIsInt($theme);
        self::assertLessThan($generic, $default);
        self::assertLessThan($theme, $generic);
    }

    public function testCustomCssComesLastAndCannotCloseTheStyleElement(): void
    {
        $css = $this->css->build([MenuCss::TARGET_MAIN => ['colors' => '/a.png']], [], '.menu-x{color:red}</style><script>alert(1)</script>');

        self::assertStringEndsWith('.menu-x{color:red}<\/style><script>alert(1)<\/script>', $css);
        self::assertStringNotContainsString('</', $css);
    }

    public function testUrlsAreEscaped(): void
    {
        self::assertSame('a\\"b\\\\cd', MenuCss::escapeUrl("a\"b\\c\nd"));
    }
}
