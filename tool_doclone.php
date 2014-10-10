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

// tool_doclone.php
// Where the actual cloning will take place

require_once(LEPTON_PATH.'/framework/class.admin.php');
require_once(LEPTON_PATH.'/framework/functions.php');
require_once(LEPTON_PATH.'/framework/class.order.php');

// create admin object depending on platform (admin tools were moved out of settings with WB 2.7)
if(file_exists(ADMIN_PATH .'/admintools/tool.php')) {
	// since Website Baker 2.7
	$admin = new admin('admintools', 'admintools');
} else {
	// Website Baker prior to 2.7
	$admin = new admin('Settings', 'settings_advanced');
}

// First get the selected page
$title = isset($_REQUEST["title"]) ?$admin->add_slashes($_REQUEST["title"]) : '';
$parent = isset($_REQUEST["parent"]) ?$_REQUEST["parent"] : '';
$pagetoclone = isset($_REQUEST["pagetoclone"]) ? (int)$_REQUEST["pagetoclone"] : 0;
$include_subs = isset($_REQUEST["include_subs"]) ? '1' : '0';

// Validate data
if($title == '') {
	$admin->print_error($MESSAGE['PAGES']['BLANK_PAGE_TITLE']);
}

// The actual pagecloning
function clone_page($title,$parent,$pagetoclone) {
	// Get objects and vars from outside this function
	global $admin, $template, $database, $TEXT, $MOD_PAGECLONER, $MESSAGE;
	
	// Get page list from database

	// Get values from page to clone from:
	$query = "SELECT * FROM ".TABLE_PREFIX."pages WHERE page_id = '$pagetoclone'";
	$get_page = $database->query($query);	 
	$is_page = $get_page->fetchRow(); 
echo 'cloning---'.$pagetoclone.' to '.$parent;
	
	// Work-out what the link and page filename should be
	if($parent == '0') {
		$link = '/'.page_filename($title);
		$filename = LEPTON_PATH.PAGES_DIRECTORY.'/'.page_filename($title).'.php';
	} else {
		$parent_section = '';
		$parent_titles = array_reverse(get_parent_titles($parent));
		foreach($parent_titles AS $parent_title) {
			$parent_section .= page_filename($parent_title).'/';
		}
		if($parent_section == '/') { $parent_section = ''; }
		$link = '/'.$parent_section.page_filename($title);
		$filename = LEPTON_PATH.PAGES_DIRECTORY.'/'.$parent_section.page_filename($title).'.php';
		make_dir(LEPTON_PATH.PAGES_DIRECTORY.'/'.$parent_section);
	}
	
	// Check if a page with same page filename exists
	$get_same_page = $database->query("SELECT page_id FROM ".TABLE_PREFIX."pages WHERE link = '$link'");
	if($get_same_page->numRows() > 0 OR file_exists(LEPTON_PATH.PAGES_DIRECTORY.$link.'.php') OR file_exists(LEPTON_PATH.PAGES_DIRECTORY.$link.'/')) {
		$admin->print_error($MESSAGE['PAGES']['PAGE_EXISTS'],'tool_clone.php?pagetoclone='.$pagetoclone);
	}
	
	// Include the ordering class
	$order = new order(TABLE_PREFIX.'pages', 'position', 'page_id', 'parent');
	// First clean order
	$order->clean($parent);
	// Get new order
	$position = $order->get_new($parent);
	
	// Insert page into pages table
	$template = $is_page['template'];
	$visibility = $is_page['visibility'];
	$admin_groups = $is_page['admin_groups'];
	$viewing_groups = $is_page['viewing_groups'];
	$query = "INSERT INTO ".TABLE_PREFIX."pages (page_title,menu_title,parent,template,target,position,visibility,searching,menu,language,admin_groups,viewing_groups,modified_when,modified_by) VALUES ('$title','$title','$parent','$template','_top','$position','$visibility','1','1','".DEFAULT_LANGUAGE."','$admin_groups','$viewing_groups','".TIME()."','".$admin->get_user_id()."')";

	$database->query($query);
	if($database->is_error()) {
		$admin->print_error($database->get_error());
	}
	
	// Get the page id
	global $page_id;
	$page_id = $database->get_one("SELECT LAST_INSERT_ID()");
	
	// Work out level
	$level = level_count($page_id);
	
	// Work out root parent
	$root_parent = root_parent($page_id);
	
	// Work out page trail
	$page_trail = get_page_trail($page_id);
	
	// Update page with new level and link
	$database->query("UPDATE ".TABLE_PREFIX."pages SET link = '$link', level = '$level', root_parent = '$root_parent', page_trail = '$page_trail' WHERE page_id = '$page_id'");
	
	// Create a new file in the /pages dir
	create_access_file($filename, $page_id, $level);
	
	// Make new sections, database
	$all_sections = array();
	$database->execute_query(
		"SELECT * FROM `".TABLE_PREFIX."sections` WHERE `page_id` = '".$pagetoclone."'",
		true,
		$all_sections
	);	 
	
	foreach($all_sections as $is_section) {

echo 'adding section..';

		// Add new record into the sections table
		$from_section = $is_section['section_id'];
		$position = $is_section['position'];
		$module = $is_section['module'];
		$block = $is_section['block'];
		$publ_start = $is_section['publ_start'];
		$publ_end = $is_section['publ_end'];
		$database->query("INSERT INTO ".TABLE_PREFIX."sections (page_id,position,module,block,publ_start,publ_end) VALUES ('$page_id','$position', '$module','$block','$publ_start','$publ_end')");
	
		// Get the section id
		global $section_id;
		$section_id = $database->get_one("SELECT LAST_INSERT_ID()");
	
		// Include the selected modules add file if it exists
		if(file_exists(LEPTON_PATH.'/modules/'.$module.'/add.php')) {
			//echo '<br>Executing '.$module.'/add.php';
			require(LEPTON_PATH.'/modules/'.$module.'/add.php');
		}
		
		// copy module settings per section
		switch( $module ) {
		
			case 'wysiwyg':
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_wysiwyg WHERE section_id = '$from_section'";
				$get_wysiwyg = $database->query($query);	 
				while ($is_wysiwyg=$get_wysiwyg->fetchRow()) {
					// Update wysiwyg section with cloned data
					$content = addslashes($is_wysiwyg['content']);
					$text = addslashes($is_wysiwyg['text']);
					$query = "UPDATE ".TABLE_PREFIX."mod_wysiwyg SET content = '$content', text = '$text' WHERE section_id = '$section_id'";
					$database->query($query);	
				}
				break;
				
			case 'form':
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_form_settings WHERE section_id = '$from_section'";
				$get_formsettings = $database->query($query);	 
				while ($is_formsettings=$get_formsettings->fetchRow()) {
						// Update formsettings section with cloned data
						$header = addslashes($is_formsettings['header']);
						$field_loop = addslashes($is_formsettings['field_loop']);
						$footer = addslashes($is_formsettings['footer']);
						$email_to = addslashes($is_formsettings['email_to']);
						$email_from = addslashes($is_formsettings['email_from']);
						$email_subject = addslashes($is_formsettings['email_subject']);
						$success_message = addslashes($is_formsettings['success_message']);
						$stored_submissions = $is_formsettings['stored_submissions'];
						$max_submissions = $is_formsettings['max_submissions'];
						$use_captcha = $is_formsettings['use_captcha'];
						$database->query("UPDATE ".TABLE_PREFIX."mod_form_settings SET header = '$header', field_loop = '$field_loop', footer = '$footer', email_to = '$email_to', email_from = '$email_from', email_subject = '$email_subject', success_message = '$success_message', max_submissions = '$max_submissions', stored_submissions = '$stored_submissions', use_captcha = '$use_captcha' WHERE section_id = '$section_id'");
				}	
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_form_fields WHERE section_id = '$from_section'";
				$get_formfield = $database->query($query);	 
				while ($is_formfield=$get_formfield->fetchRow()) {
						// Insert formfields with cloned data
						$position = $is_formfield['position'];
						$title = addslashes($is_formfield['title']);
						$type = $is_formfield['type'];
						$required = $is_formfield['required'];
						$value = $is_formfield['value'];
						$extra = addslashes($is_formfield['extra']);
						$database->query("INSERT INTO ".TABLE_PREFIX."mod_form_fields (section_id, page_id, position, title, type, required, value, extra) VALUES ('$section_id','$page_id','$position','$title','$type','$required','$value','$extra')");
				}
				break;
			
			case 'mpform':
				/**
				*	@version	0.5.2
				*	@date		2010-08-08
				*	@author		Stephan Kuehn (vBoedefeld)
				*	@package	Websitebaker - Modules: page-cloner
				*	@state		RC
				*	@notice		Just add type "mpform" for MPForm-module
				*/
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_mpform_settings WHERE section_id = '$from_section'";
				$get_formsettings = $database->query($query);	 
				while ($is_formsettings=$get_formsettings->fetchRow()) {
						// Update formsettings section with cloned data
						$header = addslashes($is_formsettings['header']);
						$field_loop = addslashes($is_formsettings['field_loop']);
						$footer = addslashes($is_formsettings['footer']);
						$email_to = addslashes($is_formsettings['email_to']);
						$email_from = addslashes($is_formsettings['email_from']);
						$email_fromname = addslashes($is_formsettings['email_fromname']);
						$email_subject = addslashes($is_formsettings['email_subject']);
						$email_text = addslashes($is_formsettings['email_text']);
						$success_page = addslashes($is_formsettings['success_page']);
						$success_text = addslashes($is_formsettings['success_text']);
						$submissions_text = addslashes($is_formsettings['submissions_text']);
						$success_email_to= addslashes($is_formsettings['success_email_to']);
						$success_email_from = addslashes($is_formsettings['success_email_from']);
						$success_email_fromname = addslashes($is_formsettings['success_email_fromname']);
						$success_email_text = addslashes($is_formsettings['success_email_text']);
						$success_email_subject = addslashes($is_formsettings['success_email_subject']);
						$stored_submissions = $is_formsettings['stored_submissions'];
						$max_submissions = $is_formsettings['max_submissions'];
						$heading_html = addslashes($is_formsettings['heading_html']);
						$short_html = addslashes($is_formsettings['short_html']);
						$long_html = addslashes($is_formsettings['long_html']);
						$email_html = addslashes($is_formsettings['email_html']);
						$uploadfile_html = addslashes($is_formsettings['uploadfile_html']);
						$use_captcha = $is_formsettings['use_captcha'];
						$upload_files_folder = addslashes($is_formsettings['upload_files_folder']);
						$date_format = addslashes($is_formsettings['date_format']);
						$max_file_size_kb= $is_formsettings['max_file_size_kb'];
						$attach_file = $is_formsettings['attach_file'];
						$upload_file_mask = addslashes($is_formsettings['upload_file_mask']);
						$upload_dir_mask = addslashes($is_formsettings['upload_dir_mask']);
						$upload_only_exts = addslashes($is_formsettings['upload_only_exts']);
						$is_following = $is_formsettings['is_following'];
						$tbl_suffix = addslashes($is_formsettings['tbl_suffix']);
						$enum_start = addslashes($is_formsettings['enum_start']);
				  $database->query("UPDATE ".TABLE_PREFIX."mod_mpform_settings SET header = '$header', field_loop = '$field_loop', footer = '$footer', email_to = '$email_to', email_from = '$email_from', email_fromname = '$email_fromname', email_subject = '$email_subject', email_text = '$email_text', success_page = '$success_page', success_text = '$success_text', submissions_text = '$submissions_text', success_email_to = '$success_email_to', success_email_from = '$success_email_from', success_email_fromname = '$success_email_fromname', success_email_text = '$success_email_text', success_email_subject = '$success_email_subject', stored_submissions = '$stored_submissions', max_submissions = '$max_submissions', heading_html = '$heading_html', short_html = '$short_html', long_html = '$long_html', email_html = '$email_html', uploadfile_html = '$uploadfile_html', use_captcha = '$use_captcha', upload_files_folder = '$upload_files_folder', date_format = '$date_format', max_file_size_kb = '$max_file_size_kb', attach_file = '$attach_file', upload_file_mask = '$upload_file_mask', upload_dir_mask = '$upload_dir_mask', upload_only_exts = '$upload_only_exts', is_following = '$is_following', tbl_suffix = '$tbl_suffix', enum_start = '$enum_start' WHERE section_id = '$section_id'");
				}	
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_mpform_fields WHERE section_id = '$from_section'";
				$get_formfield = $database->query($query);	 
				while ($is_formfield=$get_formfield->fetchRow()) {
						// Insert formfields with cloned data
						$position = $is_formfield['position'];
						$title = addslashes($is_formfield['title']);
						$type = $is_formfield['type'];
						$required = $is_formfield['required'];
						$value = $is_formfield['value'];
						$extra = addslashes($is_formfield['extra']);
						$help = addslashes($is_formfield['help']);
						$database->query("INSERT INTO ".TABLE_PREFIX."mod_mpform_fields (section_id, page_id, position, title, type, required, value, extra, help) VALUES ('$section_id','$page_id','$position','$title','$type','$required','$value','$extra', '$help')");
				}
				break;
        	
        	case 'code':
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_code WHERE section_id = '$from_section'";
				$get_code = $database->query($query);	 
				while ($is_code=$get_code->fetchRow( MYSQL_ASSOC )) {
					// Update new section with cloned data
					$content = addslashes($is_code['content']);
					$database->query("UPDATE ".TABLE_PREFIX."mod_code SET content = '$content' WHERE section_id = '$section_id'");
				}
				break;
			
			case 'code2':
				/**
				*	@version	1.0.0
				*	@date		2014-10-10
				*	@author		Dietrich Roland Pehlke (aldus)
				*	@package	LEPTON-CMS - Modules: page-cloner
				*/

				$all = array();

				$database->execute_query(
					"SELECT `content`,`whatis` FROM `".TABLE_PREFIX."mod_code2` WHERE `section_id` =".$from_section,
					true,
					$all
				);

				foreach($all as &$is_code) {
					
					$database->build_and_execute(
						"update",
						TABLE_PREFIX."mod_code2",
						$is_code,
						"section_id =".$section_id
					);
				}
				break;
		}
	}
	
	echo 'done - newpageid='.$page_id.' <br>';
	return $page_id;
}

function clone_subs($pagetoclone,$parent) {
	global $admin, $database;
	// Get page list from database

	$query = "SELECT * FROM ".TABLE_PREFIX."pages WHERE parent = '$pagetoclone'";
	$get_subpages = $database->query($query);
	
	if($get_subpages->numRows() > 0)	{
		while($page = $get_subpages->fetchRow( MYSQL_ASSOC )) {
			echo 'clonepage('.$page['page_title'].','.$parent.','.$page['page_id'].')<br>';
			$newnew_page = clone_page($page['page_title'],$parent,$page['page_id']);
			echo 'clonesubs('.$page['page_id'].','.$newnew_page.')<hr>';
			clone_subs($page['page_id'],$newnew_page);
		}
	}
}

// Clone selected page
echo 'clonepage('.$title.','.$parent.','.$pagetoclone.')<br>';
$new_page = clone_page($title,$parent,$pagetoclone);
echo 'new_pageid='.$new_page.'<hr>';

// Check if we need to clone subpages?
if ($include_subs == '1') {
	echo 'cloning subs('.$pagetoclone.','.$new_page.')<hr>';
	clone_subs($pagetoclone,$new_page);
}
	
// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(),'tool_clone.php?pagetoclone='.$pagetoclone);
} else {
	$admin->print_success($MESSAGE['PAGES']['ADDED'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}
$admin->print_footer();
?>