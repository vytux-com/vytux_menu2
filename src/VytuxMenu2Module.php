<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

/*
 * webtrees - vytux_menu2 module based on simpl_menu2
 *
 * Copyright (C) 2014 Vytautas Krivickas and vytux.com. All rights reserved.
 *
 * Copyright (C) 2013 Nigel Osborne and kiwtrees.net. All rights reserved.
 *
 * webtrees: Web based Family History software
 * Copyright (C) 2013 webtrees development team.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Module\ModuleThemeInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

use function array_filter;
use function array_values;
use function ctype_digit;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function redirect;
use function response;
use function route;
use function sha1;
use function str_contains;
use function str_replace;
use function trim;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

class VytuxMenu2Module extends AbstractModule implements ModuleCustomInterface, ModuleMenuInterface, ModuleConfigInterface, ModuleGlobalInterface
{
    use ModuleConfigTrait;
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleMenuTrait;

    public const string CUSTOM_VERSION = '2.2.0';

    // CSS class of the top-level menu.  Items use "<class>-item", "<class>-item-<id>" and "<class>-edit".
    public const string CSS_CLASS = 'menu-vytux_menu2';

    // Names used by the webtrees 1.x versions of this module.
    public const array LEGACY_MODULE_NAMES = ['vytux_menu2', 'simpl_menu2'];

    private const int SCHEMA_VERSION = 1;

    private const array MODULE_TARGETS = [MenuCss::TARGET_MAIN, MenuCss::TARGET_EDIT];

    private MenuRepository|null $repository = null;

    public function title(): string
    {
        return I18N::translate('Vytux Menu 2');
    }

    public function description(): string
    {
        return I18N::translate('Provides links to custom defined pages.');
    }

    public function customModuleAuthorName(): string
    {
        return 'Vytautas Krivickas';
    }

    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/vytux-com/vytux_menu2/master/latest.txt';
    }

    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/vytux-com/vytux_menu2/';
    }

    public function resourcesFolder(): string
    {
        return dirname(__DIR__) . '/resources/';
    }

    public function defaultMenuOrder(): int
    {
        return 50;
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        if ((int) $this->getPreference('schema_version', '0') < self::SCHEMA_VERSION) {
            $this->repository()->adoptLegacyItems(self::LEGACY_MODULE_NAMES);
            $this->setPreference('schema_version', (string) self::SCHEMA_VERSION);
        }
    }

    /**
     * @return array<string,string>
     */
    public function customTranslations(string $language): array
    {
        foreach ([$language, str_replace('-', '_', $language)] as $name) {
            $file = $this->resourcesFolder() . 'lang/' . $name . '.po';

            if (is_file($file)) {
                return (new Translation($file))->asArray();
            }
        }

        return [];
    }

    public function getMenu(Tree $tree): Menu|null
    {
        $language     = I18N::languageTag();
        $access_level = Auth::accessLevel($tree);

        $label = $this->getPreference('menu_label');
        $label = $label === '' ? I18N::translate('Menu') : e(self::translateTitle($label));

        $menu = new Menu($label, '#', self::CSS_CLASS);

        foreach ($this->repository()->items($tree->id()) as $item) {
            // Also check here, as items migrated from webtrees 1.x were never validated.
            if ($item->isVisibleTo($language, $access_level) && MenuItem::isSafeUrl($item->url)) {
                $menu->addSubmenu(new Menu(
                    e(self::translateTitle($item->title)),
                    $item->url,
                    self::CSS_CLASS . '-item ' . self::CSS_CLASS . '-item-' . $item->id,
                ));
            }
        }

        if (Auth::isAdmin()) {
            $menu->addSubmenu(new Menu(I18N::translate('Edit menus'), $this->getConfigLink(), self::CSS_CLASS . '-edit'));
        }

        return $menu->getSubmenus() === [] ? null : $menu;
    }

    public function headContent(): string
    {
        try {
            $css = $this->buildCss();
        } catch (Throwable) {
            // e.g. during setup/upgrade, before the database is available.
            return '';
        }

        return $css === '' ? '' : '<style>' . $css . '</style>';
    }

    public function buildCss(): string
    {
        $defaults = [];
        $files    = [
            MenuCss::TARGET_MAIN => 'menu.png',
            MenuCss::TARGET_EDIT => 'menu-edit.png',
            MenuCss::TARGET_ITEM => 'menu-item.png',
        ];

        foreach ($files as $target => $file) {
            foreach (MenuCss::ICON_THEMES as $theme => $size) {
                if (is_file($this->resourcesFolder() . 'images/' . $theme . '/' . $file)) {
                    $defaults[$target][$theme] = $this->assetUrl('images/' . $theme . '/' . $file);
                }
            }
        }

        $uploads = [];

        foreach (self::MODULE_TARGETS as $target) {
            foreach ($this->repository()->moduleIconKeys($target) as $key) {
                $uploads[$target][$key] = $this->iconUrl($target, $key);
            }
        }

        foreach ($this->repository()->items(null) as $item) {
            foreach ($item->icon_keys as $key) {
                $uploads[$item->id][$key] = $this->iconUrl($item->id, $key);
            }
        }

        return (new MenuCss(self::CSS_CLASS))->build($defaults, $uploads, $this->getPreference('custom_css'));
    }

    public function iconUrl(int|string $target, string $key): string
    {
        return route('module', [
            'module' => $this->name(),
            'action' => 'Icon',
            'target' => (string) $target,
            'key'    => $key,
            'v'      => $this->getPreference('icon_version', '0'),
        ]);
    }

    /**
     * Serve an uploaded icon.
     */
    public function getIconAction(ServerRequestInterface $request): ResponseInterface
    {
        $target = self::parseTarget(Validator::queryParams($request)->string('target', ''));
        $key    = Validator::queryParams($request)->string('key', IconSet::DEFAULT_KEY);
        $icon   = $target === null ? null : $this->repository()->icons($target)->get($key);

        if ($icon === null) {
            throw new HttpNotFoundException();
        }

        $etag    = '"' . sha1($icon['bytes']) . '"';
        $headers = [
            'cache-control'           => 'public,max-age=31536000',
            'etag'                    => $etag,
            'x-content-type-options'  => 'nosniff',
            'content-security-policy' => "default-src 'none'",
        ];

        if (in_array($etag, array_map('trim', explode(',', $request->getHeaderLine('if-none-match'))), true)) {
            return response('', StatusCodeInterface::STATUS_NOT_MODIFIED, $headers);
        }

        return response($icon['bytes'], StatusCodeInterface::STATUS_OK, ['content-type' => $icon['mime']] + $headers);
    }

    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $tree_service = Registry::container()->get(TreeService::class);
        $trees        = $tree_service->all();
        $tree_name    = Validator::queryParams($request)->string('tree', '');
        $tree         = $trees->get($tree_name) ?? $trees->get(Site::getPreference('DEFAULT_GEDCOM')) ?? $trees->first();

        return $this->viewResponse($this->name() . '::admin/config', [
            'custom_css'  => $this->getPreference('custom_css'),
            'items'       => $tree instanceof Tree ? $this->repository()->items($tree->id()) : $this->repository()->items(null),
            'menu_label'  => $this->getPreference('menu_label'),
            'module'      => $this,
            'title'       => $this->title(),
            'tree'        => $tree,
            'tree_names'  => $tree_service->titles(),
            'tree_titles' => $trees->mapWithKeys(static fn (Tree $tree): array => [$tree->id() => $tree->title()])->all(),
        ]);
    }

    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        return redirect(route('module', [
            'module' => $this->name(),
            'action' => 'Admin',
            'tree'   => Validator::parsedBody($request)->string('tree', ''),
        ]));
    }

    public function postAdminSettingsAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->setPreference('menu_label', trim(Validator::parsedBody($request)->string('menu_label', '')));
        $this->setPreference('custom_css', Validator::parsedBody($request)->string('custom_css', ''));

        FlashMessages::addMessage(I18N::translate('The preferences for the module “%s” have been updated.', $this->title()), 'success');

        return redirect($this->getConfigLink());
    }

    public function getAdminEditAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $item = $this->repository()->find(Validator::queryParams($request)->integer('block_id', 0));

        $tree_options = ['' => I18N::translate('All')] + Registry::container()->get(TreeService::class)->all()
            ->mapWithKeys(static fn (Tree $tree): array => [$tree->id() => $tree->title()])
            ->all();

        return $this->viewResponse($this->name() . '::admin/edit', [
            'access_levels' => Auth::accessLevelNames(),
            'icons'         => $item === null ? IconSet::empty() : $this->repository()->icons($item->id),
            'item'          => $item ?? new MenuItem(0, null, $this->repository()->nextOrder(), '', '', Auth::PRIV_PRIVATE),
            'module'        => $this,
            'themes'        => $this->themes(),
            'title'         => $item === null ? I18N::translate('Add menu') : I18N::translate('Edit menu'),
            'tree_options'  => $tree_options,
        ]);
    }

    public function postAdminEditAction(ServerRequestInterface $request): ResponseInterface
    {
        $params    = Validator::parsedBody($request);
        $tree_id   = $params->string('gedcom_id', '');
        $url       = trim($params->string('menu_address', ''));
        $languages = array_values(array_filter($params->array('languages'), is_string(...)));

        if (!MenuItem::isSafeUrl($url)) {
            FlashMessages::addMessage(I18N::translate('This menu address is not allowed.'), 'danger');

            return redirect(route('module', ['module' => $this->name(), 'action' => 'AdminEdit', 'block_id' => $params->integer('block_id', 0)]));
        }

        $id = $this->repository()->save(
            $params->integer('block_id', 0),
            ctype_digit($tree_id) ? (int) $tree_id : null,
            $params->integer('block_order', 0),
            trim($params->string('menu_title', '')),
            $url,
            $params->isBetween(Auth::PRIV_HIDE, Auth::PRIV_PRIVATE)->integer('menu_access', Auth::PRIV_PRIVATE),
            $languages,
        );

        $this->updateIcons($request, $id);

        return redirect($this->getConfigLink());
    }

    public function getAdminIconsAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $target = Validator::queryParams($request)->isInArray(self::MODULE_TARGETS)->string('target', MenuCss::TARGET_MAIN);

        return $this->viewResponse($this->name() . '::admin/icons', [
            'icons'  => $this->repository()->icons($target),
            'module' => $this,
            'target' => $target,
            'themes' => $this->themes(),
            'title'  => $target === MenuCss::TARGET_MAIN ? I18N::translate('Icons for the menu') : I18N::translate('Icons for “%s”', I18N::translate('Edit menus')),
        ]);
    }

    public function postAdminIconsAction(ServerRequestInterface $request): ResponseInterface
    {
        $target = Validator::queryParams($request)->isInArray(self::MODULE_TARGETS)->string('target', MenuCss::TARGET_MAIN);

        $this->updateIcons($request, $target);

        return redirect($this->getConfigLink());
    }

    public function postAdminDeleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->repository()->delete(Validator::queryParams($request)->integer('block_id', 0));

        return response();
    }

    public function postAdminMoveUpAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = Validator::queryParams($request);

        $this->repository()->move($params->integer('block_id', 0), -1, $params->integer('tree_id', 0) ?: null);

        return response();
    }

    public function postAdminMoveDownAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = Validator::queryParams($request);

        $this->repository()->move($params->integer('block_id', 0), 1, $params->integer('tree_id', 0) ?: null);

        return response();
    }

    /**
     * Installed themes, which can have their own icons.
     *
     * @return array<string,string> name => title
     */
    public function themes(): array
    {
        return Registry::container()->get(ModuleService::class)
            ->findByInterface(ModuleThemeInterface::class, true)
            ->filter(static fn (ModuleThemeInterface $theme): bool => IconSet::isValidKey($theme->name()))
            ->mapWithKeys(static fn (ModuleThemeInterface $theme): array => [$theme->name() => $theme->title()])
            ->all();
    }

    public function errorMessage(InvalidIconException $exception): string
    {
        return match ($exception->getCode()) {
            InvalidIconException::REASON_EMPTY      => I18N::translate('The uploaded file is empty.'),
            InvalidIconException::REASON_TOO_LARGE  => I18N::translate('The icon must be smaller than %s.', I18N::number(IconValidator::MAX_BYTES / 1024) . ' KB'),
            InvalidIconException::REASON_DIMENSIONS => I18N::translate('The icon must be no larger than %1$s × %2$s pixels.', I18N::number(IconValidator::MAX_DIMENSION), I18N::number(IconValidator::MAX_DIMENSION)),
            default                                 => I18N::translate('The icon must be a PNG, JPEG, GIF or WebP image.'),
        };
    }

    /**
     * Apply the icon uploads and removals from an admin form.
     */
    private function updateIcons(ServerRequestInterface $request, int|string $target): void
    {
        $icons    = $this->repository()->icons($target);
        $original = $icons->toJson();
        $themes   = $this->themes();
        $is_key   = static fn (mixed $key): bool => $key === IconSet::DEFAULT_KEY || (is_string($key) && isset($themes[$key]));

        foreach (Validator::parsedBody($request)->array('icon_remove') as $key) {
            if ($is_key($key)) {
                $icons = $icons->without($key);
            }
        }

        $uploads = $request->getUploadedFiles()['icon_upload'] ?? [];

        foreach (is_array($uploads) ? $uploads : [] as $key => $file) {
            if (!$is_key($key) || !$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $name = $key === IconSet::DEFAULT_KEY ? I18N::translate('Default') : $themes[$key];

            if ($file->getError() !== UPLOAD_ERR_OK) {
                FlashMessages::addMessage($name . ': ' . I18N::translate('The file could not be uploaded.'), 'danger');
                continue;
            }

            try {
                $bytes = $file->getStream()->getContents();
                $icons = $icons->with($key, IconValidator::validate($bytes), $bytes);
            } catch (InvalidIconException $exception) {
                FlashMessages::addMessage($name . ': ' . $this->errorMessage($exception), 'danger');
            }
        }

        if ($icons->toJson() !== $original) {
            $this->repository()->saveIcons($target, $icons);
            $this->setPreference('icon_version', (string) ((int) $this->getPreference('icon_version', '0') + 1));
        }
    }

    private function repository(): MenuRepository
    {
        return $this->repository ??= new MenuRepository($this);
    }

    /**
     * @return int|string|null An item ID, MenuCss::TARGET_MAIN, MenuCss::TARGET_EDIT, or null if invalid
     */
    private static function parseTarget(string $target): int|string|null
    {
        if (ctype_digit($target)) {
            return (int) $target;
        }

        return in_array($target, self::MODULE_TARGETS, true) ? $target : null;
    }

    /**
     * Titles were translated in webtrees 1.x, so that a standard English title could be shown in each language.
     */
    private static function translateTitle(string $title): string
    {
        return str_contains($title, '%') ? $title : I18N::translate($title);
    }
}
