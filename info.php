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

$module_directory 		= 'pagecloner';
$module_name 			= 'Page Cloner';
$module_function 		= 'tool';
$module_version 		= '1.1.1';
$module_platform 		= '2.x';
$module_author 			= 'John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld, cms-lab';
$module_license 		= 'GNU General Public License';
$module_license_terms	= '-';
$module_description 	= 'This addon allows you to clone a page or a complete tree with all page and sections. Copying of complete datasets from pagesections to their clones is limited to following modules: wywsiwyg, form, mpform, code, code2. Only the sections (with their default) settings are cloned for not supported modules.';
$module_guid 			= '25bfa866-2ee3-4731-8f44-f49f01c8294a';
$module_home 			= 'http://cms-lab.com';


?>
