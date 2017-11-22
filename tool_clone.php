<?php

/**
 *  @module         pagecloner
 *  @version        see info.php of this module
 *  @authors        John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld, cms-lab
 *  @copyright      2006-2010 John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld
 *  @copyright      2010-2017 cms-lab 
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

$pagetodo = isset($_POST['pagetoclone']) ? (int) $_POST['pagetoclone'] : 0;

if(0 === $pagetodo) die("no! [1]");

// check if specified page exists in the database
$aSourcePageInfo = array();
$database->execute_query(
    "SELECT * FROM `".TABLE_PREFIX."pages` WHERE `page_id` = ".$pagetodo,
    true,
    $aSourcePageInfo,
    false
);

if (0 === count($aSourcePageInfo) )
{
    die(header("Location: ".ADMIN_URL ."/admintools/tool.php?tool=pagecloner"));
}

/**
 *	Load Language file
 */
$lang = (dirname(__FILE__))."/languages/". LANGUAGE .".php";
require_once ( !file_exists($lang) ? (dirname(__FILE__))."/languages/EN.php" : $lang );

LEPTON_tools::register("page_tree");
$all_pages = array();
page_tree( 0, $all_pages );

    $aPageValues = array(
        'LEPTON_URL' => LEPTON_URL,
        'leptoken' => get_leptoken(),
        'MOD_PAGECLONER' => $MOD_PAGECLONER,
        'all_pages' => $all_pages,
        'new_page_name' => $aSourcePageInfo['page_title']." copy",
        'source_page'   => $aSourcePageInfo
    );
    
	$oTwig = lib_twig_box::getInstance();
	$oTwig->registerModule("pagecloner");
    echo $oTwig->render(
        "@pagecloner/modify_clone_settings.lte",
        $aPageValues
    );

$admin->print_footer();

