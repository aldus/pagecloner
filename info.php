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

$module_directory 		= 'pagecloner';
$module_name 			= 'Page Cloner';
$module_function 		= 'tool';
$module_version 		= '1.0.0';
$module_platform 		= '2.x';
$module_author 			= 'John Maats - Dietrich Roland Pehlke - Stephan Kuehn - vBoedefeld, cms-lab';
$module_license 		= 'GNU General Public License';
$module_license_terms	= '-';
$module_description 	= 'This addon allows you to clone a page or a complete tree with all page and sections. Copying of complete datasets from pagesections to their clones is limited to following modules: wywsiwyg, form, mpform, code, code2. Only the sections (with their default) settings are cloned for not supported modules.';
$module_guid 			= '25bfa866-2ee3-4731-8f44-f49f01c8294a';
$module_home 			= 'http://cms-lab.com';

/*
Changelog
------------------------------------------------------------------------------------------------------
    1.0.0   cms-lab
        +   recode for LEPTON 2
        +   upload to svn  

    v0.54   (Stephan Kühn; 10. August 2010)
        +   mpform support
        +   migrating the pagetree idea by pcwacht support 
        
	v0.51	(Dietrich Roland Pehlke; 04. September, 2008)
		+	add new modultype "code2" to tool_doclone.php. See comments at line 179 for details.
		+	Minor cosmetic changes in tool_doclone.php
		
	v0.50 (Christian Sommer; 05 Feb, 2008)
    + added support for the upcoming WB 2.7 version (this version works also with WB < 2.7)
			(background: admin tools were moved from admin/settings to admin/admintools with WB 2.7)

	v0.40 (John Maats; 08 Jan, 2006)
		+ fixed bug (removed dutch debugging text from line 91-93 in 'tool_doclone.php)

	v0.30 (John Maats; 08 Jan, 2006)
		+ fixed bug (forgot block number copy in section db)

	v0.20 (John Maats; 08 Jan, 2006)
		+ added copy content/settings from modules code, wysiwyg and form

	v0.10 (John Maats; 07 Jan, 2006)
		+ initial release
------------------------------------------------------------------------------------------------------
*/
?>