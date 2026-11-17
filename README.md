# vytux_menu2

Add a custom drop-down menu of links to your [webtrees](https://www.webtrees.net) site.

Ported from the `simpl_menu2` module by Nigel Osborne (kiwtrees.net), after its original author chose not to support webtrees 1.5. Ported to webtrees by Vytautas Krivickas ([vytux.com](https://vytux.com)).

> **Compatibility:** this module is for **webtrees 1.x** (1.6.0 or later). It does not work with webtrees 2.x.

Version 1.6.1, released November 2014

## Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Upgrading](#upgrading)
- [Support](#support)

## Description

Lets administrators create a menu with one item per link. Items are stored in the webtrees database and can be limited by access level, language and family tree.

## Requirements

- webtrees 1.6.0 or later

## Installation

1. Unzip the files and upload them to the webtrees `modules_vX/` folder on your web server (`X` is `3` at the time of writing, but may change in future releases).
2. Sign in to webtrees as an administrator, go to **Administration → Modules** and find **Vytux_menu2**. Make sure it is enabled.
3. Go to the **Menus** tab. Set the access level and position of the menu item. It will probably default to *Show only to managers*.
4. Click **Save**.

## Setup

Create one menu item for each link you need. Go to **Administration → Vytux_menu2** and click **Add menu item**.

1. Give each item a **title**. It appears in the drop-down, so keep it short.
2. Enter the **address (URL)** to link to, for example `forum.php`. Only pages *within* your webtrees site URL can be used.
3. Set the **access level** to choose which users can see the item.
4. Choose the **languages** to show the item in. Select none to show it in all languages. Select single languages only if you also plan to offer translated versions.
5. Set the **menu position**. This decides where the item appears in the sub-menu, and can be changed later with the up/down arrows on the Vytux_menu2 administration screen. The first item is the default when the main menu item is clicked.
6. Use **menu visibility** to show the item in all family trees, or only one.
7. Click **Save**.

### Optional changes

- **Menu title:** taken from the module title, "Menu" by default. Edit `vytux_menu2/module.php`:

  ```php
  public function getMenuTitle() {
      return WT_I18N::translate('Menu');
  }
  ```

  Replace `'Menu'` with a word or short phrase such as `'My menus'`. If you only have one menu item, the menu shows that item's title and no sub-menu is displayed.
- **Menu icons:** each theme has its own icons in the module's `themes/<theme>/` folder (`webtrees`, `clouds`, `colors`, `xenea`, ...). Add your own images of a suitable size, and update the same theme's `style.css` (the places to change are marked in comments). Do **not** reuse the original file names: they are overwritten on upgrade.
- **Fonts and colours:** edit `vytux_menu2/themes/<theme>/style.css`. These changes are overwritten on upgrade, so keep notes of them.

### Several menu items

You can add as many items as you like, but each theme you use needs extra rules in its `style.css` (in this module's `themes` folder, not webtrees' own) and some additional small icons. Details are in the comments in `style.css`.

## Upgrading

No upgrade path exists from earlier versions, as this is the first release to support webtrees 1.5 and later. The module cannot import settings from `simpl_menu2`, so either re-create your menu items or, **after** enabling the new module, run this SQL on your database:

```sql
UPDATE `wt_block` SET `module_name`='vytux_menu2' WHERE `module_name`='simpl_menu2';
```

(Adjust the `wt_` table prefix if you use a different one.)

## Support

- **Email:** [webtrees@vytux.com](mailto:webtrees@vytux.com) for vytux add-on support.
- **Forum:** general webtrees support is available on the [webtrees forum](https://www.webtrees.net/index.php/en/forum/index-en).

## Copyright

© 2013-2014 Vytautas Krivickas and vytux.com
