<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use Fisharebest\Webtrees\DB;
use Illuminate\Database\Query\Builder;

use function array_filter;
use function array_values;
use function explode;
use function implode;
use function is_int;

/**
 * Menu items are stored like the core FAQ module: one row in ##block per item, with its
 * settings in ##block_setting.  Icons are stored as JSON (see IconSet) in the "icons" setting,
 * with a list of their keys in "icon_keys" so that pages can be built without loading the images.
 *
 * The icons for the top-level menu and the "Edit menus" item are stored as module settings.
 */
final class MenuRepository
{
    private const string SETTING_ICONS     = 'icons';
    private const string SETTING_ICON_KEYS = 'icon_keys';

    private const array ITEM_SETTINGS = ['menu_title', 'menu_address', 'menu_access', 'languages', self::SETTING_ICON_KEYS];

    public function __construct(private readonly VytuxMenu2Module $module)
    {
    }

    /**
     * @param int|null $tree_id Only items for this tree (and all trees). Null for all items.
     *
     * @return list<MenuItem>
     */
    public function items(int|null $tree_id): array
    {
        $blocks = DB::table('block')
            ->where('module_name', '=', $this->module->name())
            ->when($tree_id !== null, static function (Builder $query) use ($tree_id): void {
                $query->where(static function (Builder $query) use ($tree_id): void {
                    $query->whereNull('gedcom_id')->orWhere('gedcom_id', '=', $tree_id);
                });
            })
            ->orderBy('block_order')
            ->orderBy('block_id')
            ->get(['block_id', 'gedcom_id', 'block_order']);

        if ($blocks->isEmpty()) {
            return [];
        }

        $settings = DB::table('block_setting')
            ->whereIn('block_id', $blocks->pluck('block_id')->all())
            ->whereIn('setting_name', self::ITEM_SETTINGS)
            ->get(['block_id', 'setting_name', 'setting_value'])
            ->groupBy('block_id')
            ->map(static fn ($rows): array => $rows->pluck('setting_value', 'setting_name')->all());

        $items = [];

        foreach ($blocks as $block) {
            $block_settings = $settings->get($block->block_id, []);

            $items[] = new MenuItem(
                id: (int) $block->block_id,
                tree_id: $block->gedcom_id === null ? null : (int) $block->gedcom_id,
                order: (int) $block->block_order,
                title: (string) ($block_settings['menu_title'] ?? ''),
                url: (string) ($block_settings['menu_address'] ?? ''),
                access_level: (int) ($block_settings['menu_access'] ?? 0),
                languages: self::split((string) ($block_settings['languages'] ?? '')),
                icon_keys: self::split((string) ($block_settings[self::SETTING_ICON_KEYS] ?? '')),
            );
        }

        return $items;
    }

    public function find(int $id): MenuItem|null
    {
        foreach ($this->items(null) as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }

    public function nextOrder(): int
    {
        return 1 + (int) DB::table('block')->where('module_name', '=', $this->module->name())->max('block_order');
    }

    /**
     * @param list<string> $languages
     *
     * @return int The item ID
     */
    public function save(int $id, int|null $tree_id, int $order, string $title, string $url, int $access_level, array $languages): int
    {
        if ($id !== 0 && $this->find($id) !== null) {
            DB::table('block')
                ->where('block_id', '=', $id)
                ->update(['gedcom_id' => $tree_id, 'block_order' => $order]);
        } else {
            DB::table('block')->insert([
                'gedcom_id'   => $tree_id,
                'module_name' => $this->module->name(),
                'block_order' => $order,
            ]);

            $id = DB::lastInsertId();
        }

        $this->setSetting($id, 'menu_title', $title);
        $this->setSetting($id, 'menu_address', $url);
        $this->setSetting($id, 'menu_access', (string) $access_level);
        $this->setSetting($id, 'languages', implode(',', $languages));

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            return;
        }

        DB::table('block_setting')->where('block_id', '=', $id)->delete();
        DB::table('block')->where('block_id', '=', $id)->delete();
    }

    /**
     * Swap an item with the previous (-1) or next (+1) item.
     */
    public function move(int $id, int $direction): void
    {
        $items = $this->items(null);

        foreach ($items as $index => $item) {
            if ($item->id === $id) {
                $other = $items[$index + $direction] ?? null;

                if ($other instanceof MenuItem) {
                    // Items with the same position would not swap, so renumber them.
                    $order       = $item->order;
                    $other_order = $other->order === $order ? $order + $direction : $other->order;

                    DB::table('block')->where('block_id', '=', $item->id)->update(['block_order' => $other_order]);
                    DB::table('block')->where('block_id', '=', $other->id)->update(['block_order' => $order]);
                }

                return;
            }
        }
    }

    /**
     * @param int|string $target An item ID, MenuCss::TARGET_MAIN or MenuCss::TARGET_EDIT
     */
    public function icons(int|string $target): IconSet
    {
        if (is_int($target)) {
            $json = DB::table('block_setting')
                ->where('block_id', '=', $target)
                ->where('setting_name', '=', self::SETTING_ICONS)
                ->value('setting_value');

            return IconSet::fromJson((string) $json);
        }

        return IconSet::fromJson($this->module->getPreference(self::SETTING_ICONS . '_' . $target));
    }

    /**
     * @param int|string $target An item ID, MenuCss::TARGET_MAIN or MenuCss::TARGET_EDIT
     */
    public function saveIcons(int|string $target, IconSet $icons): void
    {
        $keys = implode(',', $icons->keys());

        if (is_int($target)) {
            $this->setSetting($target, self::SETTING_ICONS, $icons->toJson());
            $this->setSetting($target, self::SETTING_ICON_KEYS, $keys);
        } else {
            $this->module->setPreference(self::SETTING_ICONS . '_' . $target, $icons->toJson());
            $this->module->setPreference(self::SETTING_ICON_KEYS . '_' . $target, $keys);
        }
    }

    /**
     * @param string $target MenuCss::TARGET_MAIN or MenuCss::TARGET_EDIT
     *
     * @return list<string>
     */
    public function moduleIconKeys(string $target): array
    {
        return self::split($this->module->getPreference(self::SETTING_ICON_KEYS . '_' . $target));
    }

    /**
     * Rename the rows of the webtrees 1.x versions of this module, so that they belong to this one.
     *
     * @param list<string> $legacy_names
     *
     * @return int Number of menu items moved
     */
    public function adoptLegacyItems(array $legacy_names): int
    {
        return DB::table('block')
            ->whereIn('module_name', $legacy_names)
            ->update(['module_name' => $this->module->name()]);
    }

    private function setSetting(int $id, string $name, string $value): void
    {
        DB::table('block_setting')->updateOrInsert(
            ['block_id' => $id, 'setting_name' => $name],
            ['setting_value' => $value],
        );
    }

    /**
     * @return list<string>
     */
    private static function split(string $value): array
    {
        return array_values(array_filter(explode(',', $value), static fn (string $x): bool => $x !== ''));
    }
}
