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

$all_pages = array();
$database->execute_query(
	"SELECT `page_id`, `page_title`, `menu_title`, `parent`, `position`, `visibility`, `level` FROM `".TABLE_PREFIX."pages` ORDER BY `parent`,`position`",
	true,
	$all_pages
);

function make_list( $aNum, &$aRefArray ) {
	global $all_pages, $TEXT;
	
	foreach($all_pages as &$ref) {
		
		if ($ref['parent'] > $aNum) break;
		
		if ($ref['parent'] == $aNum) {
			
			$ref['current_parent'] = $aNum;
			
			switch( $ref['visibility'] ) {
				case 'public':
					$ref['status_icon'] = "visible_16.png";
					$ref['status_text'] = $TEXT['PUBLIC'];
					break;
			
				case 'private':
					$ref['status_icon'] = "private_16.png";
					$ref['status_text'] = $TEXT['PRIVATE'];
					break;
			
				case 'registered':
					$ref['status_icon'] = "keys_16.png";
					$ref['status_text'] = $TEXT['REGISTERED'];
					break;
				
				case 'hidden':
					$ref['status_icon'] = "hidden_16.png";
					$ref['status_text'] = $TEXT['HIDDEN'];
					break;
				
				case 'none':
					$ref['status_icon'] = "none_16.png";
					$ref['status_text'] = $TEXT['NONE'];
					break;
				
				case 'deleted':
					$ref['status_icon'] = "deleted_16.png";
					$ref['status_text'] = $TEXT['DELETED'];
					break;

			}
			
			$temp = array();
			make_list( $ref['page_id'], $temp);
			
			$n = count($temp);
			$ref['display_plus'] = ($n > 0) ? 1 : 0;
			$ref['subpage'] = ($n > 0) ? $temp : 0;
			
			$aRefArray[] = &$ref;
		}
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