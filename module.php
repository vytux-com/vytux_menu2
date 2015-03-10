<?php
namespace Vytux\webtrees_vytux_menu2;

// webtrees - vytux_menu2 module based on simpl_menu2
//
// Copyright (C) 2014 Vytautas Krivickas and vytux.com. All rights reserved.
// 
// Copyright (C) 2013 Nigel Osborne and kiwtrees.net. All rights reserved.
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
use Zend_Translate;
use Fisharebest\Webtrees as webtrees;

class VytuxMenu2MenuModule extends webtrees\Module implements webtrees\ModuleBlockInterface, webtrees\ModuleConfigInterface, webtrees\ModuleMenuInterface {

	public function __construct() {
		parent::__construct('vytux_menu2');
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR . $this->getName() . '/language')) {
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.mo')) {
				webtrees\I18N::addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.php')) {
				webtrees\I18N::addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.csv')) {
				webtrees\I18N::addTranslation(
					new Zend_Translate('csv', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.csv', WT_LOCALE)
				);
			}
		}
	}

	// Extend class WT_Module
	public function getTitle() {
		return webtrees\I18N::translate('Vytux Menu 2');
	}

	public function getMenuTitle() {
		return webtrees\I18N::translate('Menu');
	}

	// Extend class WT_Module
	public function getDescription() {
		return webtrees\I18N::translate('Provides links to custom defined pages.');
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 50;
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return webtrees\Auth::PRIV_NONE;
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin_config';
	}

	// Implement class WT_Module_Block
	public function getBlock($block_id, $template=true, $cfg=null) {
	}

	// Implement class WT_Module_Block
	public function loadAjax() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isUserBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isGedcomBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function configureBlock($block_id) {
	}

	// Implement WT_Module_Menu
	public function getMenu() {
		global $controller, $WT_TREE;
		$menu_titles = $this->getMenuList();	
		$lang = '';
		
		$min_block = webtrees\Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();
		
		foreach ($menu_titles as $items) {
			$languages = webtrees\get_block_setting($items->block_id, 'languages');
			if (in_array(WT_LOCALE, explode(',', $languages))) {
				$lang = WT_LOCALE;
			} else {
				$lang = '';
			}
		}

		$default_block = webtrees\Database::prepare(
			"SELECT ##block.block_id FROM `##block`, `##block_setting` WHERE block_order=? AND module_name=? AND ##block.block_id = ##block_setting.block_id AND ##block_setting.setting_value LIKE ?"
		)->execute(array($min_block, $this->getName(), '%' . $lang . '%'))->fetchOne();
		
		$main_menu_address = webtrees\Database::prepare(
			"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
		)->execute(array($default_block, 'menu_address'))->fetchOne();
		
		if (count($menu_titles)>1) {
			$main_menu_title = $this->getMenuTitle();
		} else {
			$main_menu_title = webtrees\Database::prepare(
				"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
			)->execute(array($default_block, 'menu_title'))->fetchOne();
		}
		
		if (webtrees\Auth::isSearchEngine()) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/')) {
			echo '<link rel="stylesheet" href="' . WT_MODULES_DIR . $this->getName() . '/themes/' . webtrees\Theme::theme()->themeId() . '/style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="' . WT_MODULES_DIR . $this->getName() . '/themes/webtrees/style.css" type="text/css">';
		}

		//-- main menu item
		$menu = new webtrees\Menu($main_menu_title, $main_menu_address, $this->getName(), 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($menu_titles as $items) {
			if (count($menu_titles)>1) {
				$languages = webtrees\get_block_setting($items->block_id, 'languages');
				if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->menu_access >= webtrees\Auth::accessLevel($WT_TREE)) {
					$submenu = new webtrees\Menu(webtrees\I18N::translate($items->menu_title), $items->menu_address, $this->getName() . '-' . str_replace(' ', '', $items->menu_title));
					$menu->addSubmenu($submenu);
				}
			}
		}
		if (webtrees\Auth::isAdmin()) {
			$submenu = new webtrees\Menu(webtrees\I18N::translate('Edit menus'), $this->getConfigLink(), $this->getName() . '-edit');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'show':
			$this->show();
			break;
		case 'admin_config':
			$this->config();
			break;
		case 'admin_delete':
			$this->delete();
			$this->config();
			break;
		case 'admin_edit':
			$this->edit();
			break;
		case 'admin_movedown':
			$this->moveDown();
			$this->config();
			break;
		case 'admin_moveup':
			$this->moveUp();
			$this->config();
			break;
		default:
			http_response_code(404);
		}
	}

	// Action from the configuration page
	private function edit() {
		global $WT_TREE;
		
		if (webtrees\Filter::postBool('save') && webtrees\Filter::checkCsrf()) {
			$block_id = webtrees\Filter::post('block_id');
			if ($block_id) {
				webtrees\Database::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					webtrees\Filter::post('gedcom_id'),
					(int)webtrees\Filter::post('block_order'),
					$block_id
				));
			} else {
				webtrees\Database::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					webtrees\Filter::post('gedcom_id'),
					$this->getName(),
					(int)webtrees\Filter::post('block_order')
				));
				$block_id = webtrees\Database::getInstance()->lastInsertId();
			}
			webtrees\set_block_setting($block_id, 'menu_title',		webtrees\Filter::post('menu_title'));
			webtrees\set_block_setting($block_id, 'menu_address',	webtrees\Filter::post('menu_address'));
			webtrees\set_block_setting($block_id, 'menu_access',		webtrees\Filter::post('menu_access'));
			$languages = array();
			foreach (webtrees\I18N::installedLanguages() as $code=>$name) {
				if (webtrees\Filter::postBool('lang_' . $code)) {
					$languages[] = $code;
				}
			}
			webtrees\set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id = webtrees\Filter::get('block_id');
			$controller = new webtrees\PageController();
			$controller->restrictAccess(webtrees\Auth::isEditor($WT_TREE));
			if ($block_id) {
				$controller->setPageTitle(webtrees\I18N::translate('Edit menu'));
				$menu_title   = webtrees\get_block_setting($block_id, 'menu_title');
				$menu_address = webtrees\get_block_setting($block_id, 'menu_address');
				$menu_access  = webtrees\get_block_setting($block_id, 'menu_access');
				$block_order  = webtrees\Database::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id    = webtrees\Database::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(webtrees\I18N::translate('Add menu'));
				$menu_access  = 1;
				$menu_title   = '';
				$menu_address = '';
				$block_order  = webtrees\Database::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
                $gedcom_id    = $WT_TREE->getTreeId();
			}
			$controller->pageHeader();
			?>
			
			<ol class="breadcrumb small">
				<li><a href="admin.php"><?php echo webtrees\I18N::translate('Control panel'); ?></a></li>
				<li><a href="admin_modules.php"><?php echo webtrees\I18N::translate('Module administration'); ?></a></li>
				<li><a href="module.php?mod=<?php echo $this->getName(); ?>&mod_action=admin_config"><?php echo webtrees\I18N::translate($this->getTitle()); ?></a></li>
				<li class="active"><?php echo $controller->getPageTitle(); ?></li>
			</ol>
			
			<form class="form-horizontal" method="POST" action="#" name="menu" id="menuForm">
				<?php echo webtrees\Filter::getCsrf(); ?>
				<input type="hidden" name="save" value="1">
				<input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
				<h3><?php echo webtrees\I18N::translate('General'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_title">
						<?php echo webtrees\I18N::translate('Title'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="menu_title"
							size="90"
							name="menu_title"
							required
							type="text"
							value="<?php echo webtrees\Filter::escapeHtml($menu_title); ?>"
							>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php echo webtrees\I18N::translate('Add your menu title here'); ?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_address">
						<?php echo webtrees\I18N::translate('Menu address'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="menu_address"
							size="90"
							name="menu_address"
							required
							type="text"
							value="<?php echo webtrees\Filter::escapeHtml($menu_address); ?>"
							>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php echo webtrees\I18N::translate('Add your menu address here'); ?>
					</span>
				</div>
				
				<h3><?php echo webtrees\I18N::translate('Languages'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="lang_*">
						<?php echo webtrees\I18N::translate('Show this menu for which languages?'); ?>
					</label>
					<div class="row col-sm-9">
						<?php 
							$accepted_languages=explode(',', webtrees\get_block_setting($block_id, 'languages'));
							foreach (webtrees\I18N::installedLanguages() as $locale => $language) {
								$checked = in_array($locale, $accepted_languages) ? 'checked' : ''; 
						?>
								<div class="col-sm-3">
									<label class="checkbox-inline "><input type="checkbox" name="lang_<?php echo $locale; ?>" <?php echo $checked; ?> ><?php echo $language; ?></label>
								</div>
						<?php 
							}
						?>
					</div>
				</div>
				
				<h3><?php echo webtrees\I18N::translate('Visibility and Access'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo webtrees\I18N::translate('Menu position'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="position"
							name="block_order"
							size="3"
							required
							type="number"
							value="<?php echo webtrees\Filter::escapeHtml($block_order); ?>"
						>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo webtrees\I18N::translate('This field controls the order in which the menu items are displayed.'),
							'<br><br>',
							webtrees\I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other menu items later. For example, if you use the numbers 1, 6, 11, 16, you can later insert menu items with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert menu items in front of the first one.'),
							'<br><br>',
							webtrees\I18N::translate('When more than one menu item has the same position number, only one of these menu items will be visible.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo webtrees\I18N::translate('Menu visibility'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo webtrees\select_edit_control('gedcom_id', webtrees\Tree::getIdList(), webtrees\I18N::translate('All'), $WT_TREE->getTreeId(), 'class="form-control"'); ?>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo webtrees\I18N::translate('You can determine whether this menu item will be visible regardless of family tree, or whether it will be visible only to the current family tree.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_access">
						<?php echo webtrees\I18N::translate('Access level'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo webtrees\edit_field_access_level('menu_access', $menu_access, 'class="form-control"'); ?>
					</div>
				</div>
				
				<div class="row col-sm-9 col-sm-offset-3">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-check"></i>
						<?php echo webtrees\I18N::translate('save'); ?>
					</button>
					<button class="btn" type="button" onclick="window.location='<?php echo $this->getConfigLink(); ?>';">
						<i class="fa fa-close"></i>
						<?php echo webtrees\I18N::translate('cancel'); ?>
					</button>
				</div>
			</form>
<?php
		}
	}

	private function delete() {
		global $WT_TREE;

        if (webtrees\Auth::isManager($WT_TREE)) {
			$block_id = webtrees\Filter::get('block_id');

			webtrees\Database::prepare(
				"DELETE FROM `##block_setting` WHERE block_id=?"
			)->execute(array($block_id));

			webtrees\Database::prepare(
				"DELETE FROM `##block` WHERE block_id=?"
			)->execute(array($block_id));
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function moveUp() {
		global $WT_TREE;

        if (webtrees\Auth::isManager($WT_TREE)) {
			$block_id = webtrees\Filter::get('block_id');

			$block_order = webtrees\Database::prepare(
				"SELECT block_order FROM `##block` WHERE block_id=?"
			)->execute(array($block_id))->fetchOne();

			$swap_block = webtrees\Database::prepare(
				"SELECT block_order, block_id".
				" FROM `##block`".
				" WHERE block_order=(".
				"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
				" ) AND module_name=?".
				" LIMIT 1"
			)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
			if ($swap_block) {
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order=? WHERE block_id=?"
				)->execute(array($swap_block->block_order, $block_id));
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order=? WHERE block_id=?"
				)->execute(array($block_order, $swap_block->block_id));
			}
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function moveDown() {
		global $WT_TREE;

        if (webtrees\Auth::isManager($WT_TREE)) {
			$block_id = webtrees\Filter::get('block_id');

			$block_order = webtrees\Database::prepare(
				"SELECT block_order FROM `##block` WHERE block_id=?"
			)->execute(array($block_id))->fetchOne();

			$swap_block = webtrees\Database::prepare(
				"SELECT block_order, block_id".
				" FROM `##block`".
				" WHERE block_order=(".
				"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
				" ) AND module_name=?".
				" LIMIT 1"
			)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
			if ($swap_block) {
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order=? WHERE block_id=?"
				)->execute(array($swap_block->block_order, $block_id));
				webtrees\Database::prepare(
					"UPDATE `##block` SET block_order=? WHERE block_id=?"
				)->execute(array($block_order, $swap_block->block_id));
			}
		} else {
			header('Location: ' . WT_BASE_URL);
			exit;
		}
	}

	private function config() {
		global $WT_TREE;
		$controller = new webtrees\PageController();
		$controller
			->restrictAccess(webtrees\Auth::isManager($WT_TREE))
			->setPageTitle($this->getTitle())
			->pageHeader();

		$args                = array();
		$args['module_name'] = $this->getName();
        $args['tree_id']     = $WT_TREE->getTreeId();
		$items = webtrees\Database::prepare(
			"SELECT block_id, block_order, gedcom_id, bs1.setting_value AS menu_title, bs2.setting_value AS menu_address".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name = :module_name".
			" AND bs1.setting_name = 'menu_title'".
			" AND bs2.setting_name = 'menu_address'".
			" AND IFNULL(gedcom_id, :tree_id) = :tree_id".
			" ORDER BY block_order"
		)->execute($args)->fetchAll();

		unset($args['tree_id']);
		$min_block_order = webtrees\Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name = :module_name"
		)->execute($args)->fetchOne();

		$max_block_order = webtrees\Database::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name = :module_name"
		)->execute($args)->fetchOne();
		?>
		
		<style>
			.text-left-not-xs, .text-left-not-sm, .text-left-not-md, .text-left-not-lg {
				text-align: left;
			}
			.text-center-not-xs, .text-center-not-sm, .text-center-not-md, .text-center-not-lg {
				text-align: center;
			}
			.text-right-not-xs, .text-right-not-sm, .text-right-not-md, .text-right-not-lg {
				text-align: right;
			}
			.text-justify-not-xs, .text-justify-not-sm, .text-justify-not-md, .text-justify-not-lg {
				text-align: justify;
			}

			@media (max-width: 767px) {
				.text-left-not-xs, .text-center-not-xs, .text-right-not-xs, .text-justify-not-xs {
					text-align: inherit;
				}
				.text-left-xs {
					text-align: left;
				}
				.text-center-xs {
					text-align: center;
				}
				.text-right-xs {
					text-align: right;
				}
				.text-justify-xs {
					text-align: justify;
				}
			}
			@media (min-width: 768px) and (max-width: 991px) {
				.text-left-not-sm, .text-center-not-sm, .text-right-not-sm, .text-justify-not-sm {
					text-align: inherit;
				}
				.text-left-sm {
					text-align: left;
				}
				.text-center-sm {
					text-align: center;
				}
				.text-right-sm {
					text-align: right;
				}
				.text-justify-sm {
					text-align: justify;
				}
			}
			@media (min-width: 992px) and (max-width: 1199px) {
				.text-left-not-md, .text-center-not-md, .text-right-not-md, .text-justify-not-md {
					text-align: inherit;
				}
				.text-left-md {
					text-align: left;
				}
				.text-center-md {
					text-align: center;
				}
				.text-right-md {
					text-align: right;
				}
				.text-justify-md {
					text-align: justify;
				}
			}
			@media (min-width: 1200px) {
				.text-left-not-lg, .text-center-not-lg, .text-right-not-lg, .text-justify-not-lg {
					text-align: inherit;
				}
				.text-left-lg {
					text-align: left;
				}
				.text-center-lg {
					text-align: center;
				}
				.text-right-lg {
					text-align: right;
				}
				.text-justify-lg {
					text-align: justify;
				}
			}
		</style>
		
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo webtrees\I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo webtrees\I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		
		<div class="row">
			<div class="col-sm-4 col-xs-12">
				<form class="form">
					<label for="ged" class="sr-only">
						<?php echo webtrees\I18N::translate('Family tree'); ?>
					</label>
					<input type="hidden" name="mod" value="<?php echo  $this->getName(); ?>">
					<input type="hidden" name="mod_action" value="admin_config">
					<div class="col-sm-9 col-xs-9" style="padding:0;">
						<?php echo webtrees\select_edit_control('ged', webtrees\Tree::getNameList(), null, $WT_TREE->getName(), 'class="form-control"'); ?>
					</div>
					<div class="col-sm-3" style="padding:0;">
						<input type="submit" class="btn btn-primary" value="<?php echo webtrees\I18N::translate('show'); ?>">
					</div>
				</form>
			</div>
			<span class="visible-xs hidden-sm hidden-md hidden-lg" style="display:block;"></br></br></span>
			<div class="col-sm-4 text-center text-left-xs col-xs-12">
				<p>
					<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit" class="btn btn-primary">
						<i class="fa fa-plus"></i>
						<?php echo webtrees\I18N::translate('Add Menu'); ?>
					</a>
				</p>
			</div>
			<div class="col-sm-4 text-right text-left-xs col-xs-12">		
				<?php // TODO: Move to internal item/page
				if (file_exists(WT_MODULES_DIR . $this->getName() . '/readme.html')) { ?>
					<a href="<?php echo WT_MODULES_DIR . $this->getName(); ?>/readme.html" class="btn btn-info">
						<i class="fa fa-newspaper-o"></i>
						<?php echo webtrees\I18N::translate('ReadMe'); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		
		<table class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo webtrees\I18N::translate('Position'); ?></th>
					<th class="col-sm-4"><?php echo webtrees\I18N::translate('Menu title'); ?></th>
					<th class="col-sm-4"><?php echo webtrees\I18N::translate('Menu address'); ?></th>
					<th class="col-sm-2" colspan=4><?php echo webtrees\I18N::translate('Controls'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($items as $item): ?>
				<tr>
					<td>
						<?php echo $item->block_order, ', ';
						if ($item->gedcom_id == null) {
							echo webtrees\I18N::translate('All');
						} else {
							echo webtrees\Tree::findById($item->gedcom_id)->getTitleHtml();
						} ?>
					</td>
					<td>
						<?php echo webtrees\Filter::escapeHtml(webtrees\I18N::translate($item->menu_title)); ?>
					</td>
					<td>
						<?php echo webtrees\Filter::escapeHtml(substr(webtrees\I18N::translate($item->menu_address), 0, 1)=='<' ? webtrees\I18N::translate($item->menu_address) : nl2br(webtrees\I18N::translate($item->menu_address))); ?>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit&amp;block_id=<?php echo $item->block_id; ?>">
							<div class="icon-edit">&nbsp;</div>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_moveup&amp;block_id=<?php echo $item->block_id; ?>">
							<?php
								if ($item->block_order == $min_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-uarrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_movedown&amp;block_id=<?php echo $item->block_id; ?>">
							<?php
								if ($item->block_order == $max_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-darrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_delete&amp;block_id=<?php echo $item->block_id; ?>"
							onclick="return confirm('<?php echo webtrees\I18N::translate('Are you sure you want to delete this menu?'); ?>');">
							<div class="icon-delete">&nbsp;</div>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
<?php
	}

	// Return the list of menus
	private function getMenuList() {
		global $WT_TREE;
		
		return webtrees\Database::prepare(
			"SELECT block_id, bs1.setting_value AS menu_title, bs2.setting_value AS menu_access, bs3.setting_value AS menu_address".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" JOIN `##block_setting` bs3 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='menu_title'".
			" AND bs2.setting_name='menu_access'".
			" AND bs3.setting_name='menu_address'".
			" AND (gedcom_id IS NULL OR gedcom_id=?)".
			" ORDER BY block_order"
		)->execute(array($this->getName(), $WT_TREE->getTreeId()))->fetchAll();
	}
	
}
return new VytuxMenu2MenuModule;