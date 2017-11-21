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

$pagetodo = isset($_GET['pagetoclone']) ? (int) $_GET['pagetoclone'] : 0;

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

$admin = new LEPTON_admin('admintools', 'admintools');

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

	// Parent page list

	function parent_list($parent) {
		global $admin, $database, $template;
	
		$admin_group_id = $admin->get_group_id();
		$admin_user_id = $admin->get_user_id();
	
		$all_pages = array();

	
echo $oTwig->render(
    "@pagecloner/modify_clone_settings.lte",
    $aPageValues
);


	$twig_util->resolve_path("template.lte");
	
	echo $parser->render( 
		$twig_modul_namespace."template.lte",
		$pagecloner_vars
}	

$admin->print_footer();

