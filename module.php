<?php
namespace Webtrees;

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

class vytux_menu2_WT_Module extends Module implements ModuleBlockInterface, ModuleConfigInterface, ModuleMenuInterface {
/*
	public function __construct() {
		parent::__construct();
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR.$this->getName().'/language')) {
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('csv', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv', WT_LOCALE)
				);
			}
		}
	}
*/
	// Extend class WT_Module
	public function getTitle() {
		return I18N::translate('Vytux Menu 2');
	}

	public function getMenuTitle() {
		return I18N::translate('Menu');
	}

	// Extend class WT_Module
	public function getDescription() {
		return I18N::translate('Provides links to custom defined pages.');
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 50;
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return WT_PRIV_NONE;
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod='.$this->getName().'&amp;mod_action=admin_config';
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
		global $controller, $SEARCH_SPIDER;
		$menu_titles=$this->getMenuList();	
		$lang='';
		
		$min_block=Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();
		
		foreach ($menu_titles as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if (in_array(WT_LOCALE, explode(',', $languages))) {
				$lang=WT_LOCALE;
			} else {
				$lang='';
			}
		}

		$default_block=Database::prepare(
			"SELECT ##block.block_id FROM `##block`, `##block_setting` WHERE block_order=? AND module_name=? AND ##block.block_id = ##block_setting.block_id AND ##block_setting.setting_value LIKE ?"
		)->execute(array($min_block, $this->getName(), '%'.$lang.'%'))->fetchOne();
		
		$main_menu_address = Database::prepare(
			"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
		)->execute(array($default_block, 'menu_address'))->fetchOne();
		
		if (count($menu_titles)>1) {
			$main_menu_title = $this->getMenuTitle();
		} else {
			$main_menu_title = Database::prepare(
				"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
			)->execute(array($default_block, 'menu_title'))->fetchOne();
		}
		
		if ($SEARCH_SPIDER) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/')) {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/webtrees/style.css" type="text/css">';
		}

		//-- main menu item
		$menu = new Menu($main_menu_title, $main_menu_address, $this->getName(), 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($menu_titles as $items) {
			if (count($menu_titles)>1) {
				$languages=get_block_setting($items->block_id, 'languages');
				if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->menu_access>=WT_USER_ACCESS_LEVEL) {
					$submenu = new Menu(I18N::translate($items->menu_title), $items->menu_address, $this->getName().'-'.str_replace(' ', '', $items->menu_title));
					$menu->addSubmenu($submenu);
				}
			}
		}
		if (Auth::isAdmin()) {
			$submenu = new Menu(I18N::translate('Edit menus'), $this->getConfigLink(), $this->getName().'-edit');
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

		require_once WT_ROOT.'includes/functions/functions_edit.php';

		if (Filter::postBool('save') && Filter::checkCsrf()) {
			$block_id=Filter::post('block_id');
			if ($block_id) {
				Database::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					Filter::post('gedcom_id'),
					(int)Filter::post('block_order'),
					$block_id
				));
			} else {
				Database::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					Filter::post('gedcom_id'),
					$this->getName(),
					(int)Filter::post('block_order')
				));
				$block_id=Database::getInstance()->lastInsertId();
			}
			set_block_setting($block_id, 'menu_title',		Filter::post('menu_title'));
			set_block_setting($block_id, 'menu_address',	Filter::post('menu_address'));
			set_block_setting($block_id, 'menu_access',		Filter::post('menu_access'));
			$languages=array();
			foreach (I18N::installed_languages() as $code=>$name) {
				if (Filter::postBool('lang_'.$code)) {
					$languages[]=$code;
				}
			}
			set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id=Filter::get('block_id');
			$controller=new PageController();
			if ($block_id) {
				$controller->setPageTitle(I18N::translate('Edit menu'));
				$menu_title=get_block_setting($block_id, 'menu_title');
				$menu_address=get_block_setting($block_id, 'menu_address');
				$menu_access=get_block_setting($block_id, 'menu_access');
				$block_order=Database::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id=Database::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(I18N::translate('Add menu'));
				$menu_access=1;
				$menu_title='';
				$menu_address='';
				$block_order=Database::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
				$gedcom_id=WT_GED_ID;
			}
			$controller->pageHeader();
			?>
			
			<ol class="breadcrumb small">
				<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
				<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
				<li><a href="module.php?mod=<?php echo $this->getName(); ?>&mod_action=admin_config"><?php echo I18N::translate($this->getTitle()); ?></a></li>
				<li class="active"><?php echo $controller->getPageTitle(); ?></li>
			</ol>
			
			<form class="form-horizontal" method="POST" action="#" name="menu" id="menuForm">
				<?php echo Filter::getCsrf(); ?>
				<input type="hidden" name="save" value="1">
				<input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
				<h3><?php echo I18N::translate('General'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_title">
						<?php echo I18N::translate('Title'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="menu_title"
							size="90"
							name="menu_title"
							required
							type="text"
							value="<?php echo Filter::escapeHtml($menu_title); ?>"
							>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php echo I18N::translate('Add your menu title here'); ?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_address">
						<?php echo I18N::translate('Menu address'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="menu_address"
							size="90"
							name="menu_address"
							required
							type="text"
							value="<?php echo Filter::escapeHtml($menu_address); ?>"
							>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php echo I18N::translate('Add your menu address here'); ?>
					</span>
				</div>
				
				<h3><?php echo I18N::translate('Languages'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="lang_*">
						<?php echo I18N::translate('Show this menu for which languages?'); ?>
					</label>
					<div class="row col-sm-9">
						<?php 
							$accepted_languages=explode(',', get_block_setting($block_id, 'languages'));
							foreach (I18N::installed_languages() as $locale => $language) {
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
				
				<h3><?php echo I18N::translate('Visibility and Access'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo I18N::translate('Menu position'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="position"
							name="block_order"
							size="3"
							required
							type="number"
							value="<?php echo Filter::escapeHtml($block_order); ?>"
						>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo I18N::translate('This field controls the order in which the menu items are displayed.'),
							'<br><br>',
							I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other menu items later. For example, if you use the numbers 1, 6, 11, 16, you can later insert menu items with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert menu items in front of the first one.'),
							'<br><br>',
							I18N::translate('When more than one menu item has the same position number, only one of these menu items will be visible.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo I18N::translate('Menu visibility'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo select_edit_control('gedcom_id', WT_Tree::getIdList(), I18N::translate('All'), $gedcom_id, 'class="form-control"'); ?>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo I18N::translate('You can determine whether this menu item will be visible regardless of family tree, or whether it will be visible only to the current family tree.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="menu_access">
						<?php echo I18N::translate('Access level'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo edit_field_access_level('menu_access', $menu_access, 'class="form-control"'); ?>
					</div>
				</div>
				
				<div class="row col-sm-9 col-sm-offset-3">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-check"></i>
						<?php echo I18N::translate('save'); ?>
					</button>
					<button class="btn" type="button" onclick="window.location='<?php echo $this->getConfigLink(); ?>';">
						<i class="fa fa-close"></i>
						<?php echo I18N::translate('cancel'); ?>
					</button>
				</div>
			</form>
<?php
		}
	}

	private function delete() {
		$block_id=Filter::get('block_id');

		Database::prepare(
			"DELETE FROM `##block_setting` WHERE block_id=?"
		)->execute(array($block_id));

		Database::prepare(
			"DELETE FROM `##block` WHERE block_id=?"
		)->execute(array($block_id));
	}

	private function moveUp() {
		$block_id=Filter::get('block_id');

		$block_order=Database::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=Database::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function moveDown() {
		$block_id=Filter::get('block_id');

		$block_order=Database::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=Database::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function config() {
		require_once 'includes/functions/functions_edit.php';

		$controller=new PageController();
		$controller->setPageTitle($this->getTitle());
		$controller->pageHeader();

		$items=Database::prepare(
			"SELECT block_id, block_order, gedcom_id, bs1.setting_value AS menu_title, bs2.setting_value AS menu_address".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='menu_title'".
			" AND bs2.setting_name='menu_address'".
			" AND IFNULL(gedcom_id, ?)=?".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID, WT_GED_ID))->fetchAll();

		$min_block_order=Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		$max_block_order=Database::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();
		?>
		
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		
		<div class="row">
			<div class="col-sm-4">
				<form class="form form-inline">
					<label for="ged" class="sr-only">
						<?php echo I18N::translate('Family tree'); ?>
					</label>
					<input type="hidden" name="mod" value="<?php echo  $this->getName(); ?>">
					<input type="hidden" name="mod_action" value="admin_config">
					<?php echo select_edit_control('ged', WT_Tree::getNameList(), null, WT_GEDCOM, 'class="form-control"'); ?>
					<input type="submit" class="btn btn-primary" value="<?php echo I18N::translate('show'); ?>">
				</form>
			</div>
			<div class="col-sm-4 text-center">
				<p>
					<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit" class="btn btn-primary">
						<i class="fa fa-plus"></i>
						<?php echo I18N::translate('Add menu item'); ?>
					</a>
				</p>
			</div>
			<div class="col-sm-4 text-right">		
				<?php // TODO: Move to internal item/page
				if (file_exists(WT_MODULES_DIR.$this->getName().'/readme.html')) { ?>
					<a href="<?php echo WT_MODULES_DIR.$this->getName(); ?>/readme.html" class="btn btn-info">
						<i class="fa fa-newspaper-o"></i>
						<?php echo I18N::translate('ReadMe'); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		
		<table class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo I18N::translate('Position'); ?></th>
					<th class="col-sm-4"><?php echo I18N::translate('Menu title'); ?></th>
					<th class="col-sm-4"><?php echo I18N::translate('Menu address'); ?></th>
					<th class="col-sm-2" colspan=4><?php echo I18N::translate('Controls'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($items as $item): ?>
				<tr>
					<td>
						<?php echo $item->block_order, ', ';
						if ($item->gedcom_id==null) {
							echo I18N::translate('All');
						} else {
							echo WT_Tree::get($item->gedcom_id)->titleHtml();
						} ?>
					</td>
					<td>
						<?php echo Filter::escapeHtml(I18N::translate($item->menu_title)); ?>
					</td>
					<td>
						<?php echo Filter::escapeHtml(substr(I18N::translate($item->menu_address), 0, 1)=='<' ? I18N::translate($item->menu_address) : nl2br(I18N::translate($item->menu_address))); ?>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit&amp;block_id=<?php echo $item->block_id; ?>">
							<div class="icon-edit">&nbsp;</div>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_moveup&amp;block_id=<?php echo $item->block_id; ?>">
							<?php
								if ($item->block_order==$min_block_order) {
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
								if ($item->block_order==$max_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-darrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_delete&amp;block_id=<?php echo $item->block_id; ?>"
							onclick="return confirm('<?php echo I18N::translate('Are you sure you want to delete this menu?'); ?>');">
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
		return Database::prepare(
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
		)->execute(array($this->getName(), WT_GED_ID))->fetchAll();
	}
	
}
