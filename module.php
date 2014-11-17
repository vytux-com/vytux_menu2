<?php
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
use WT\Auth;

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class vytux_menu2_WT_Module extends WT_Module implements WT_Module_Menu, WT_Module_Block, WT_Module_Config {

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
	// Extend class WT_Module
	public function getTitle() {
		return 'Vytux_menu2';
	}

	public function getMenuTitle() {
		return WT_I18N::translate('Menu');
	}

	// Extend class WT_Module
	public function getDescription() {
		return WT_I18N::translate('Provides links to custom defined pages.');
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
		
		$min_block=WT_DB::prepare(
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

		$default_block=WT_DB::prepare(
			"SELECT ##block.block_id FROM `##block`, `##block_setting` WHERE block_order=? AND module_name=? AND ##block.block_id = ##block_setting.block_id AND ##block_setting.setting_value LIKE ?"
		)->execute(array($min_block, $this->getName(), '%'.$lang.'%'))->fetchOne();
		
		$main_menu_address = WT_DB::prepare(
			"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
		)->execute(array($default_block, 'menu_address'))->fetchOne();
		
		if (count($menu_titles)>1) {
			$main_menu_title = $this->getMenuTitle();
		} else {
			$main_menu_title = WT_DB::prepare(
				"SELECT setting_value FROM `##block_setting` WHERE block_id=? AND setting_name=?"
			)->execute(array($default_block, 'menu_title'))->fetchOne();
		}
		
		if ($SEARCH_SPIDER) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR.$this->getName().'/'.WT_THEME_URL)) {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/'.WT_THEME_URL.'style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/webtrees/style.css" type="text/css">';
		}

		//-- main menu item
		$menu = new WT_Menu($main_menu_title, $main_menu_address, $this->getName(), 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($menu_titles as $items) {
			if (count($menu_titles)>1) {
				$languages=get_block_setting($items->block_id, 'languages');
				if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->menu_access>=WT_USER_ACCESS_LEVEL) {
					$submenu = new WT_Menu(WT_I18N::translate($items->menu_title), $items->menu_address, $this->getName().'-'.str_replace(' ', '', $items->menu_title));
					$menu->addSubmenu($submenu);
				}
			}
		}
		if (Auth::isAdmin()) {
			$submenu = new WT_Menu(WT_I18N::translate('Edit menus'), $this->getConfigLink(), $this->getName().'-edit');
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
			$this->movedown();
			$this->config();
			break;
		case 'admin_moveup':
			$this->moveup();
			$this->config();
			break;
		}
	}

	// Action from the configuration page
	private function edit() {

		require_once WT_ROOT.'includes/functions/functions_edit.php';

		if (WT_Filter::postBool('save') && WT_Filter::checkCsrf()) {
			$block_id=WT_Filter::post('block_id');
			if ($block_id) {
				WT_DB::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					(int)WT_Filter::post('block_order'),
					$block_id
				));
			} else {
				WT_DB::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					$this->getName(),
					(int)WT_Filter::post('block_order')
				));
				$block_id=WT_DB::getInstance()->lastInsertId();
			}
			set_block_setting($block_id, 'menu_title',		WT_Filter::post('menu_title'));
			set_block_setting($block_id, 'menu_address',	WT_Filter::post('menu_address'));
			set_block_setting($block_id, 'menu_access',		WT_Filter::post('menu_access'));
			$languages=array();
			foreach (WT_I18N::installed_languages() as $code=>$name) {
				if (WT_Filter::postBool('lang_'.$code)) {
					$languages[]=$code;
				}
			}
			set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id=WT_Filter::get('block_id');
			$controller=new WT_Controller_Page();
			if ($block_id) {
				$controller->setPageTitle(WT_I18N::translate('Edit menu'));
				$menu_title=get_block_setting($block_id, 'menu_title');
				$menu_address=get_block_setting($block_id, 'menu_address');
				$menu_access=get_block_setting($block_id, 'menu_access');
				$block_order=WT_DB::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id=WT_DB::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(WT_I18N::translate('Add menu'));
				$menu_access=1;
				$menu_title='';
				$menu_address='';
				$block_order=WT_DB::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
				$gedcom_id=WT_GED_ID;
			}
			$controller->pageHeader();

			echo '<style>
					#module_admin, #module_lang {background:#fdf5e6;border: 1px inset #d9d6c4;margin: 0 0 10px 0;padding: 5px;overflow:hidden;}
					#module_admin {float:left;width:450px;}
					#module_lang {float:left;margin:0 30px;}
					#module_admin label {float:left;clear:both;line-height:20px; margin-top:10px;}
					#module_admin input, #module_admin select {float:left;clear:both;height:25px;font-size:110%;}
					#module_save {clear:both;}
					html[dir=rtl] #module_admin, html[dir=rtl] #module_lang, html[dir=rtl] #module_admin input, html[dir=rtl] #module_admin select, html[dir=rtl] #module_admin label {float:right;}
				</style>
				<div id="page_help">', help_link('add_menu', $this->getName()), '</div>
				<form name="menu" method="post" action="#">
					', WT_Filter::getCsrf() ,'
					<input type="hidden" name="save" value="1">
					<input type="hidden" name="block_id" value="', $block_id, '">
					<div id="module_admin">
						<label for "menu_title">', WT_I18N::translate('Title'), '</label>
							<input type="text" id="menu_title" name="menu_title" size="51" tabindex="1" value="', $menu_title, '" placeholder="', WT_I18N::translate('Add your menu title here'), '" autofocus>
						<label for "menu_title">', WT_I18N::translate('Menu address'), '</label>
							<input type="text" id="menu_address" name="menu_address" size="51" tabindex="2" value="', $menu_address, '" placeholder="', WT_I18N::translate('Add your menu address here'), '">
						<label for "menu_access">', WT_I18N::translate('Access level'), '</label>';
							echo edit_field_access_level('menu_access', $menu_access, 'tabindex="3"'),'
						<label for "block_order">', WT_I18N::translate('Menu position'), help_link('menu_position', $this->getName()), '</label>
							<input type="text" id="block_order" name="block_order" size="3" tabindex="4" value="', $block_order, '">
						<label for "gedcom_id">', WT_I18N::translate('Menu visibility'), help_link('menu_visibility', $this->getName()), '</label>';
							echo select_edit_control('gedcom_id', WT_Tree::getIdList(), '', $gedcom_id, 'tabindex="5"'),'
					</div>
					<div id="module_lang">
						<label for "languages">', WT_I18N::translate('Show this menu for which languages?'), '</label>';
							$languages=get_block_setting($block_id, 'languages');
							echo edit_language_checkboxes('lang_', $languages),'
					</div>
					<div id="module_save">
						<input type="submit" value="', WT_I18N::translate('Save'), '" tabindex="6">
						&nbsp;<input type="button" value="', WT_I18N::translate('Cancel'), '" onclick="window.location=\''.$this->getConfigLink().'\';" tabindex="7">
					</div>
				</form>',
			exit;
		}
	}

	private function delete() {
		$block_id=WT_Filter::get('block_id');

		WT_DB::prepare(
			"DELETE FROM `##block_setting` WHERE block_id=?"
		)->execute(array($block_id));

		WT_DB::prepare(
			"DELETE FROM `##block` WHERE block_id=?"
		)->execute(array($block_id));
	}

	private function moveup() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function movedown() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function config() {
		require_once 'includes/functions/functions_edit.php';

		$controller=new WT_Controller_Page();
		$controller->setPageTitle($this->getTitle());
		$controller->pageHeader();

		$items=WT_DB::prepare(
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

		$min_block_order=WT_DB::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		$max_block_order=WT_DB::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		echo
			'<p><form method="get" action="', WT_SCRIPT_NAME ,'">',
			WT_I18N::translate('Family tree'), ' ',
			'<input type="hidden" name="mod", value="', $this->getName(), '">',
			'<input type="hidden" name="mod_action", value="admin_config">',
			select_edit_control('ged', WT_Tree::getNameList(), null, WT_GEDCOM),
			'<input type="submit" value="', WT_I18N::translate('show'), '">',
			'</form></p>';

		echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_edit">', WT_I18N::translate('Add menu item'), '</a>',
			help_link('add_menu', $this->getName());

		echo '<table id="faq_edit">';
		if (empty($items)) {
			echo '<tr><td class="error center" colspan="5">', WT_I18N::translate('The menu is empty.'), '</td></tr></table>';
		} else {
			$trees=WT_Tree::getAll();
			foreach ($items as $item) {
				// NOTE: Print the position of the current item
				echo '<tr class="faq_edit_pos"><td>';
				echo WT_I18N::translate('Position item'), ': ', $item->block_order, ', ';
				if ($item->gedcom_id==null) {
					echo WT_I18N::translate('All');
				} else {
					echo $trees[$item->gedcom_id]->tree_title_html;
				}
				echo '</td>';
				// NOTE: Print the edit options of the current item
				echo '<td>';
				if ($item->block_order==$min_block_order) {
					echo '&nbsp;';
				} else {
					echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_moveup&amp;block_id=', $item->block_id, ' "class="icon-uarrow"></a>';
					echo help_link('moveup_pages', $this->getName());
				}
				echo '</td><td>';
				if ($item->block_order==$max_block_order) {
					echo '&nbsp;';
				} else {
					echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_movedown&amp;block_id=', $item->block_id, ' "class="icon-darrow"></a>';
					echo help_link('movedown_menu', $this->getName());
				}
				echo '</td><td>';
				echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_edit&amp;block_id=', $item->block_id, '">', WT_I18N::translate('Edit'), '</a>';
				echo help_link('edit_menu', $this->getName());
				echo '</td><td>';
				echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_delete&amp;block_id=', $item->block_id, '" onclick="return confirm(\'', WT_I18N::translate('Are you sure you want to delete this menu item?'), '\');">', WT_I18N::translate('Delete'), '</a>';
				echo help_link('delete_menu', $this->getName());
				echo '</td></tr>';
				// NOTE: Print the title text of the current item
				echo '<tr><td colspan="5">';
				echo '<div class="faq_edit_item">';
				echo '<div class="faq_edit_title">', WT_I18N::translate($item->menu_title), '</div>';
				// NOTE: Print the body text of the current item
				echo '<div>', substr(WT_I18N::translate($item->menu_address), 0, 1)=='<' ? WT_I18N::translate($item->menu_address) : nl2br(WT_I18N::translate($item->menu_address)), '</div></div></td></tr>';
			}
			echo '</table>';
		}
	}

	// Return the list of menus
	private function getMenuList() {
		return WT_DB::prepare(
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
