# vytux_menu2

[![CI](https://github.com/vytux-com/vytux_menu2/actions/workflows/ci.yml/badge.svg)](https://github.com/vytux-com/vytux_menu2/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/vytux/vytux_menu2)](https://packagist.org/packages/vytux/vytux_menu2)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

A [webtrees](https://www.webtrees.net) custom module that adds a drop-down menu of your own links to the main menu bar.

Originally based on the `simpl_menu2` module by Nigel Osborne (kiwtrees.net), ported to webtrees by Vytautas Krivickas ([vytux.com](https://vytux.com)).

## Features

- One menu, with as many links as you like. Each link has a **title**, an **address (URL)**, an **access level** (visitors, members, managers or nobody), optional **languages** and an optional **family tree**.
- Links can be reordered with up/down arrows.
- Optional **icons**, uploaded in the control panel and stored in the webtrees database. Nothing needs to be copied into the module folder, so icons survive upgrades and Composer installs.
  - Upload a **default icon** for each link, and optionally a different icon for any **theme**, including custom themes.
  - PNG, JPEG, GIF and WebP images up to 100 KB are accepted. SVG is not accepted, because it can contain scripts.
- Ships with icons matching the webtrees, colors, clouds and xenea themes. Other themes (such as fab and minimal) show no icons unless you upload one for them.
- A **Custom CSS** box for your own styling, and a configurable label for the top-level menu.
- Data from the webtrees 1.x versions of this module (and from `simpl_menu2`) is migrated automatically.
- Translated into Danish, French, German and Lithuanian. Strings without a translation fall back to English.

## Requirements

- webtrees 2.2 or later
- PHP 8.3 or later

## Installation

### With Composer (recommended)

From your webtrees root folder:

```
composer require vytux/vytux_menu2
```

The module is installed into `modules_v4/vytux_menu2`.

### Manually

Download the latest release, unzip it and upload the folder to `modules_v4/vytux_menu2/` on your web server.

### Enable the menu

1. Sign in to webtrees as an administrator and go to **Control panel → Modules → All modules**. Find **Vytux Menu 2** and make sure it is enabled.
2. Go to **Control panel → Modules → Menus**. Set the access level and position of the menu. By default it may be shown to managers only.
3. Click **Save**.

## Setup

Go to **Control panel → Modules → Menus → Vytux Menu 2** (the wrench icon), or choose **Menu → Edit menus** on any page.

1. Click **Add menu** and give the link a **title**. It appears in the drop-down, so keep it short. Titles are passed through the webtrees translation system, so a standard English phrase is shown translated where a translation exists.
2. Enter the **menu address**, for example `index.php?route=/tree/demo/individual-list` or `https://example.com/`. `javascript:` and `data:` addresses are not allowed.
3. Choose the **access level**, **languages** (none = all languages) and **family tree** (all, or one).
4. Optionally upload **icons**, then click **Save**.

Use **Icons for the menu** and **Icons for “Edit menus”** to change the icon of the top-level menu and of the *Edit menus* link.

### How icons work

The core webtrees themes draw menu icons with CSS `::before` rules, and this module does the same. Each icon is served from the database by the module, with long-lived cache headers, and is added to the page with a small `<style>` block. Icons are chosen in this order:

1. an icon uploaded for the current theme;
2. the uploaded default icon (only on themes that show menu icons: webtrees, colors, clouds and xenea);
3. the icon shipped with the module for the current theme.

### CSS classes

| Element | Class |
| --- | --- |
| Top-level menu (`li`) | `menu-vytux_menu2` |
| Every link | `menu-vytux_menu2-item` |
| One link | `menu-vytux_menu2-item-<id>`, where `<id>` is the `block_id` in the address of its edit page |
| *Edit menus* link | `menu-vytux_menu2-edit` |

webtrees adds `wt-theme-<theme>` to the `<body>`, so you can style a single theme, for example:

```css
.wt-theme-xenea .menu-vytux_menu2-item-3 { font-weight: bold; }
```

Icon sizes are CSS variables (`--menu-vytux_menu2-size` for the top-level icon and `--menu-vytux_menu2-sub-size` for the links), so they can be changed in **Custom CSS**.

## Upgrading

With Composer run `composer update vytux/vytux_menu2`. Otherwise replace the module folder with the files from the latest release.

### From the webtrees 1.x version

1. Upgrade webtrees to 2.2 first, following the webtrees upgrade instructions.
2. Delete the old `modules_v3/vytux_menu2` folder, and install this module as above.
3. Open any page. Your existing menu links (stored with module name `vytux_menu2` or `simpl_menu2`) are adopted automatically, with their titles, addresses, access levels, languages, trees and order.

Things that are **not** carried over:

- Icons and CSS you added to the old `themes/<theme>/` folders. Upload the icons again in the control panel, and paste CSS changes into **Custom CSS**. The old per-item classes (e.g. `vytux_menu2-MyTitle`) are now `menu-vytux_menu2-item-<id>`.
- Addresses that point to webtrees 1.x pages, such as `module.php?mod=…` or `individual.php?pid=…`, need updating to their webtrees 2 equivalents.
- Items whose address starts with `javascript:`, `data:` or `vbscript:` are not shown in the menu until their address is changed.
- Code changes to the menu title. Use the **Menu label** setting instead.

## Development

Run the unit tests with:

```
composer install
composer test
```

CI runs these tests on PHP 8.3 to 8.6 for every push and pull request.

The code lives in `src/`: `VytuxMenu2Module.php` (module, menu and control panel), `MenuRepository.php` (database), `MenuCss.php` (icon CSS), and `IconSet.php` / `IconValidator.php` (stored icons and upload checks). Views are in `resources/views/admin/` and the default icons in `resources/images/<theme>/`.

## Translations

Translations are gettext `.po` files in `resources/lang/`, named after the webtrees language code (for example `lt.po` or `en_GB.po`). Pull requests adding or improving a language are welcome.

## Support

Please report bugs and request features on the [issue tracker](https://github.com/vytux-com/vytux_menu2/issues). You can also email [webtrees@vytux.com](mailto:webtrees@vytux.com), or ask general webtrees questions on the [webtrees forum](https://www.webtrees.net/index.php/forum).

## Licence

Copyright © 2014 Vytautas Krivickas and vytux.com. Based on simpl_menu2, copyright © 2013 Nigel Osborne and kiwtrees.net.

Released under the GNU General Public License, version 2 or (at your option) any later version. See the licence header in the source files.
