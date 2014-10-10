<?php

/**
 *  @module         pagecloner
 *  @version        see info.php of this module
 *  @authors        John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld, cms-lab
 *  @copyright      2006-2010 John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld
 *  @copyright      2010-2014 cms-lab 
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

/**
 *	Load Language file
 */
$lang = (dirname(__FILE__))."/languages/". LANGUAGE .".php";
require_once ( !file_exists($lang) ? (dirname(__FILE__))."/languages/EN.php" : $lang );

/**	*******************************
 *	Try to get the template-engine.
 */
global $parser, $loader;
require( dirname(__FILE__)."/register_parser.php" );

function make_list($parent, &$editable_pages) {
	global $database, $TEXT;

	$all_pages = array();
	$database->execute_query(
		"SELECT * FROM ".TABLE_PREFIX."pages WHERE parent = '".$parent."' AND visibility != 'deleted' ORDER BY position ASC",
		true,
		$all_pages
	);

	$num_pages = count($all_pages);

	foreach($all_pages as $page) {
		
		$page['current_parent'] = $parent;
		
		switch( $page['visibility'] ) {
			case 'public':
				$page['status_icon'] = "visible_16.png";
				$page['status_text'] = $TEXT['PUBLIC'];
				break;
			
			case 'private':
				$page['status_icon'] = "private_16.png";
				$page['status_text'] = $TEXT['PRIVATE'];
				break;
			
			case 'registered':
				$page['status_icon'] = "keys_16.png";
				$page['status_text'] = $TEXT['REGISTERED'];
				break;
				
			case 'hidden':
				$page['status_icon'] = "hidden_16.png";
				$page['status_text'] = $TEXT['HIDDEN'];
				break;
				
			case 'none':
				$page['status_icon'] = "none_16.png";
				$page['status_text'] = $TEXT['NONE'];
				break;
				
			case 'deleted':
				$page['status_icon'] = "deleted_16.png";
				$page['status_text'] = $TEXT['DELETED'];
				break;

		}
		
		$get_page_subs = $database->query("SELECT `page_id` FROM `".TABLE_PREFIX."pages` WHERE `parent`= '".$page['page_id']."'");
		$num_subpages = $get_page_subs->numRows();
			
		if($num_subpages == 0) {
			$page['display_plus'] = 0;
			$page['subpage'] = 0;
		} else {
			$page['display_plus'] = 1;
			$sub_page = array();
			make_list( $page['page_id'], $sub_page );
			$page['subpage'] = $sub_page;
		}
		
		$editable_pages[] = $page;
	}

}

// Generate pages list
if($admin->get_permission('pages_view') == true) {

	$editable_pages = array();
	make_list(0, $editable_pages);
	
	
	$pagecloner_vars = array(
		'THEME_URL'	=> THEME_URL,
		'LEPTON_URL' => LEPTON_URL,
		'TEXT' => $TEXT,
		'MOD_PAGECLONER' => $MOD_PAGECLONER,
		'editable_pages' => $editable_pages
	);

	$twig_util->resolve_path("modify.lte");
	
	echo $parser->render( 
		$twig_modul_namespace."modify.lte",
		$pagecloner_vars
	);
}
?>