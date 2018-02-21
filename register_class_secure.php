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


$files_to_register = array(
	'tool_clone.php',
	'tool_doclone.php'
);

LEPTON_secure::getInstance()->accessFiles( $files_to_register );

?>