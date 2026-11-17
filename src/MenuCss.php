<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use function implode;
use function is_int;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Builds the CSS that adds icons to the menu, in the same way as the core webtrees themes,
 * i.e. using ::before on the menu link (top level) and on each dropdown item.
 *
 * webtrees adds the class "wt-theme-<name>" to <body>, so theme-specific icons are scoped with it.
 */
final class MenuCss
{
    public const string TARGET_MAIN = 'main';
    public const string TARGET_EDIT = 'edit';
    public const string TARGET_ITEM = 'item';

    /**
     * Core themes which show icons in the menus, with the size of their icons.
     * The default (DEFAULT_KEY) icons are only shown on these themes.
     *
     * @var array<string,array{main:int,sub:int,block:bool}>
     */
    public const array ICON_THEMES = [
        'clouds'   => ['main' => 22, 'sub' => 22, 'block' => false],
        'colors'   => ['main' => 22, 'sub' => 22, 'block' => false],
        'webtrees' => ['main' => 50, 'sub' => 24, 'block' => true],
        'xenea'    => ['main' => 28, 'sub' => 22, 'block' => true],
    ];

    // Sizes for other themes, e.g. when an icon is uploaded specifically for a theme without icons.
    private const array OTHER_THEMES = ['main' => 24, 'sub' => 20, 'block' => false];

    public function __construct(private readonly string $css_class)
    {
    }

    /**
     * @param array<int|string,array<string,string>> $defaults Target => [theme name => URL]
     * @param array<int|string,array<string,string>> $uploads  Target => [theme name or IconSet::DEFAULT_KEY => URL]
     *
     * Targets are TARGET_MAIN, TARGET_EDIT, TARGET_ITEM (all items) or an item ID.
     */
    public function build(array $defaults, array $uploads, string $custom_css = ''): string
    {
        $rules = [];

        // Later rules win, as the selectors have equal specificity:
        // defaults, then uploaded default icons, then uploaded theme-specific icons.
        foreach ($defaults as $target => $icons) {
            foreach ($icons as $theme => $url) {
                $rules[] = $this->rule($target, $theme, $url);
            }
        }

        foreach ([true, false] as $default_pass) {
            foreach ($uploads as $target => $icons) {
                foreach ($icons as $key => $url) {
                    if (($key === IconSet::DEFAULT_KEY) === $default_pass) {
                        $rules[] = $this->rule($target, $key, $url);
                    }
                }
            }
        }

        if ($rules !== []) {
            $rules = [...$this->sizeRules(), ...$rules];
        }

        $custom_css = trim($custom_css);

        if ($custom_css !== '') {
            $rules[] = $custom_css;
        }

        if ($rules === []) {
            return '';
        }

        // Prevent the CSS from closing the <style> element.
        return str_replace('</', '<\\/', implode("\n", $rules));
    }

    /**
     * Sizes are CSS variables, so they can be changed with custom CSS.
     *
     * @return list<string>
     */
    private function sizeRules(): array
    {
        $rules = ['.' . $this->css_class . '{' . $this->sizeVariables(self::OTHER_THEMES) . '}'];

        foreach (self::ICON_THEMES as $theme => $size) {
            $rules[] = '.wt-theme-' . $theme . ' .' . $this->css_class . '{' . $this->sizeVariables($size) . '}';
        }

        return $rules;
    }

    /**
     * @param array{main:int,sub:int,block:bool} $size
     */
    private function sizeVariables(array $size): string
    {
        $prefix = '--' . $this->css_class;

        return
            $prefix . '-size:' . $size['main'] . 'px;' .
            $prefix . '-sub-size:' . $size['sub'] . 'px;' .
            $prefix . '-display:' . ($size['block'] ? 'block' : 'inline-block') . ';' .
            $prefix . '-margin:' . ($size['block'] ? '0 auto' : '0 .25rem');
    }

    private function rule(int|string $target, string $key, string $url): string
    {
        $prefix = '--' . $this->css_class;

        if ($key === IconSet::DEFAULT_KEY) {
            $scope = [];
            foreach (self::ICON_THEMES as $theme => $size) {
                $scope[] = '.wt-theme-' . $theme;
            }
            $scope = ':is(' . implode(',', $scope) . ')';
        } else {
            $scope = '.wt-theme-' . $key;
        }

        if ($target === self::TARGET_MAIN) {
            $selector = '.' . $this->css_class . '>.nav-link::before';
            $box      = 'display:var(' . $prefix . '-display);margin:var(' . $prefix . '-margin);width:var(' . $prefix . '-size);height:var(' . $prefix . '-size)';
        } else {
            $selector = '.' . $this->css_class . '-' . (is_int($target) ? 'item-' . $target : $target) . '::before';
            $box      = 'display:inline-block;vertical-align:middle;width:var(' . $prefix . '-sub-size);height:var(' . $prefix . '-sub-size)';
        }

        return $scope . ' ' . $selector . '{content:"";' . $box . ';background:url("' . self::escapeUrl($url) . '") center/contain no-repeat}';
    }

    public static function escapeUrl(string $url): string
    {
        $url = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $url);

        return str_replace(['\\', '"'], ['\\\\', '\\"'], $url);
    }
}
