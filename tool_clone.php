<?php

/**
 *  @module         pagecloner
 *  @version        see info.php of this module
 *  @authors        John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld, cms-lab
 *  @copyright      2006-2010 John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld
 *  @copyright      2010-2016 cms-lab 
 *  @license        GNU General Public License
 *  @license terms  see info.php of this module
 *
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('LEPTON_PATH')) {	
	include(LEPTON_PATH.'/framework/class.secure.php'); 
} else {
	$oneback = "../";
	$root = $oneback;
	$level = 1;
	while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
		$root .= $oneback;
		$level += 1;
	}
	if (file_exists($root.'/framework/class.secure.php')) { 
		include($root.'/framework/class.secure.php'); 
	} else {
		trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
	}
}
// end include class.secure.php

require_once(LEPTON_PATH.'/framework/class.admin.php');
require_once(LEPTON_PATH.'/framework/summary.functions.php');

// make sure that a page to clone was specified
$pagetodo = isset($_GET['pagetoclone']) ? (int) $_GET['pagetoclone'] : 0;

// check if specified page exists in the database
$query = "SELECT * FROM ".TABLE_PREFIX."pages WHERE page_id = '$pagetodo'";
$get_pagetodo = $database->query($query);   
$is_pagetodo = $get_pagetodo->fetchRow();

$admintool_link = ADMIN_URL .'/admintools/index.php';
$pageclone_link = ADMIN_URL .'/admintools/tool.php?tool=pagecloner';

// redirect to pageclone main page if no valid page was specified
if ($pagetodo < 1 || !$is_pagetodo) {
	die(header('Location: '.$pageclone_link));
} 

$admin = new admin('admintools', 'admintools');

/**
 *	Load Language file
 */
$lang = (dirname(__FILE__))."/languages/". LANGUAGE .".php";
require_once ( !file_exists($lang) ? (dirname(__FILE__))."/languages/EN.php" : $lang );

// And... action
if ($pagetodo > 0 && $is_pagetodo) {
	// write out admint tool header
	?>
	<h4 style="margin: 0; border-bottom: 1px solid #DDD; padding-bottom: 5px;">
		<a href="<?php echo $admintool_link;?>"><?php echo $HEADING['ADMINISTRATION_TOOLS']; ?></a>
		->
		<a href="<?php echo $pageclone_link;?>">Page Cloner Tree</a>
		-> <?php echo $is_pagetodo['menu_title'];?>
	</h4>
	<?php


	// Setup template object
	$template = new Template(LEPTON_PATH.'/modules/pagecloner/templates');
	$template->set_file('page', 'template.lte');
	$template->set_block('page', 'main_block', 'main');

	// Parent page list

	function parent_list($parent) {
		global $admin, $database, $template;
	
		$admin_group_id = $admin->get_group_id();
		$admin_user_id = $admin->get_user_id();
	
		$all_pages = array();
	
		$database->execute_query(
			"SELECT * FROM `".TABLE_PREFIX."pages` WHERE `parent`='".$parent."' AND `visibility` != 'deleted' ORDER BY position ASC",
			true,
			$all_pages
		);
	
		foreach($all_pages as $page) {
			// Stop users from adding pages with a level of more than the set page level limit
			if( $page['level']+1 <= PAGE_LEVEL_LIMIT) {
				// Get user perms
				$admin_groups = explode(',', str_replace('_', '', $page['admin_groups']));
				$admin_users = explode(',', str_replace('_', '', $page['admin_users']));
				if(is_numeric(array_search($admin_group_id, $admin_groups)) OR is_numeric(array_search($admin_user_id, $admin_users))) {
					$can_modify = true;
				} else {
					$can_modify = false;
				}
				// Title -'s prefix
				for($i = 1, $title_prefix = ''; $i <= $page['level']; $i++) { $title_prefix .= ' - '; }
				
				$template->set_var(array(
					'ID' => $page['page_id'],
					'TITLE' => ($title_prefix.$page['page_title'])
					)
				);
				if( $can_modify == true) {
					$template->set_var('DISABLED', '');
				} else {
					$template->set_var('DISABLED', ' disabled');
				}
				$template->parse('page_list2', 'page_list_block2', true);
			}
			parent_list($page['page_id']);
		}
	}

	$template->set_block('main_block', 'page_list_block2', 'page_list2');
	if($admin->get_permission('pages_add_l0') == true) {
		$template->set_var(array(
				'ID' => '0',
				'TITLE' => $TEXT['NONE'],
				'SELECTED' => ' selected',
				'DISABLED' => ''
			)
		);
		$template->parse('page_list2', 'page_list_block2', true);
	}

	parent_list(0);

	// Insert language headings
	$template->set_var(array(
		'HEADING_ADD_PAGE' => $MOD_PAGECLONER['CLONE_PAGETO'],
		)
	);

	// Insert language text and messages
	$template->set_var(array(
		'TEXT_TITLE' => $TEXT['TITLE'],
		'TEXT_DEFAULT' => $is_pagetodo['menu_title'].' - Copy',
		'TEXT_TYPE' => $TEXT['TYPE'],
		'TEXT_PARENT' => $TEXT['PARENT'],
		'TEXT_INCLUDE_SUBS' => $MOD_PAGECLONER['INCLUDE_SUBS'],
		'TEXT_VISIBILITY' => $TEXT['VISIBILITY'],
		'TEXT_PUBLIC' => $TEXT['PUBLIC'],
		'TEXT_PRIVATE' => $TEXT['PRIVATE'],
		'TEXT_REGISTERED' => $TEXT['REGISTERED'],
		'TEXT_HIDDEN' => $TEXT['HIDDEN'],
		'TEXT_NONE' => $TEXT['NONE'],
		'TEXT_NONE_FOUND' => $TEXT['NONE_FOUND'],
		'TEXT_PAGETODO' => $pagetodo,
		'TEXT_ADD' => $MOD_PAGECLONER['ADD'],
		'TEXT_RESET' => $TEXT['RESET'],
		'TEXT_ABORT' => $MOD_PAGECLONER['ABORT'],		
		'TEXT_ADMINISTRATORS' => $TEXT['ADMINISTRATORS'],								
		'TEXT_PRIVATE_VIEWERS' => $TEXT['PRIVATE_VIEWERS'],
		'TEXT_REGISTERED_VIEWERS' => $TEXT['REGISTERED_VIEWERS'],

		'CANCEL_ONCLICK'	=> 'javascript: window.location = \''.ADMIN_URL.'/admintools/tool.php?tool=pagecloner\';'		
		)
	);

	// Insert permissions values
	if($admin->get_permission('pages_add') != true) {
		$template->set_var('DISPLAY_ADD', 'hide');
	} elseif($admin->get_permission('pages_add_l0') != true AND $editable_pages == 0) {
		$template->set_var('DISPLAY_ADD', 'hide');
	}

	// Parse template object
	$template->parse('main', 'main_block', false);
	$template->pparse('output', 'page');
}	
$admin->print_footer();

?>