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

$admin = new LEPTON_admin('admintools', 'admintools');

// First get the selected page
$title = isset($_POST["title"]) ? addslashes($_POST['title']): '';
$parent = isset($_POST["parent"]) ? $_POST["parent"] : '';
$pagetoclone = isset($_POST["pagetoclone"]) ? intval($_POST["pagetoclone"]) : 0;
$include_subs = isset($_POST["include_subs"]) ? '1' : '0';

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

    echo 'cloning page '.$pagetoclone.' to '.$parent;
	
	// Work-out what the link and page filename should be
	if($parent == '0') {
		$link = '/'.save_filename($title);
		$filename = LEPTON_PATH.PAGES_DIRECTORY.'/'.save_filename($title).'.php';
	} else {
		$parent_section = '';
		$parent_titles = array_reverse(get_parent_titles($parent));
		foreach($parent_titles AS $parent_title) {
			$parent_section .= save_filename($parent_title).'/';
		}
		if($parent_section == '/') { $parent_section = ''; }
		$link = '/'.$parent_section.save_filename($title);
		$filename = LEPTON_PATH.PAGES_DIRECTORY.'/'.$parent_section.save_filename($title).'.php';
		make_dir(LEPTON_PATH.PAGES_DIRECTORY.'/'.$parent_section);
	}
	
	// Check if a page with same page filename exists
	$get_same_page = $database->query("SELECT page_id FROM ".TABLE_PREFIX."pages WHERE link = '$link'");
	if($get_same_page->numRows() > 0 OR file_exists(LEPTON_PATH.PAGES_DIRECTORY.$link.'.php') OR file_exists(LEPTON_PATH.PAGES_DIRECTORY.$link.'/')) {
		$admin->print_error($MESSAGE['PAGES']['PAGE_EXISTS'],'tool_clone.php?pagetoclone='.$pagetoclone);
	}
	
	// Include the ordering class
	$order = new LEPTON_order(TABLE_PREFIX.'pages', 'position', 'page_id', 'parent');
	// First clean order
	$order->clean($parent);
	// Get new order
	$position = $order->get_new($parent);
	
	// Insert page into pages table
	$template = $is_page['template'];
	$visibility = $is_page['visibility'];
	$admin_groups = $is_page['admin_groups'];
	$viewing_groups = $is_page['viewing_groups'];
	
	/**
	 *	Aldus - 2016-09-20
	 */
	$fields = array(
		"page_title"	=> $title,
		"menu_title"	=> $title,
		"parent"		=> $parent,
		"template"		=> $template,
		"target"		=> '_top',		// *
		"position"		=> $position,
		"visibility"	=> $visibility,
		"searching"		=> 1,			// *
		"menu"			=> 1,			// *
		"language"		=> DEFAULT_LANGUAGE,
		"admin_groups"	=> $admin_groups,
		"admin_users"	=> $is_page['admin_users'],
		"viewing_groups"	=> $viewing_groups,
		"modified_when"		=> time(),
		"modified_by"		=> $admin->get_user_id(),
		"link"			=> $link,	//	**
		"description"	=> $is_page['description'],
		"keywords"		=> $is_page['keywords'],
		"page_trail"	=> $is_page['page_trail'],
		"viewing_users"	=> $is_page['viewing_users']
	);
			
	$database->build_and_execute(
		'insert',
		TABLE_PREFIX."pages",
		$fields
	);
	
	// Get the page id
	global $page_id;
	$page_id = $database->db_handle->lastInsertId();
	
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
	
	foreach($all_sections as &$is_section) {

		echo '<p>Adding section ...</p>';

		// Add new record into the sections table
		$from_section = $is_section['section_id'];
		$position = $is_section['position'];

		$fields = array(
			'page_id'	=> $page_id,
			'position'	=> $is_section['position'],
			'module'	=> $is_section['module'],
			'block'		=> $is_section['block'],
			'publ_start' => $is_section['publ_start'],
			'publ_end'	=> $is_section['publ_end']
		);
		
		$database->build_and_execute(
			"insert",
			TABLE_PREFIX."sections",
			$fields
		);
	
		// Get the section id
		global $section_id;
		$section_id = $database->db_handle->lastInsertId();
	
		// Include the selected modules add file if it exists
		if(file_exists(LEPTON_PATH.'/modules/'.$is_section['module'].'/add.php')) {
			require(LEPTON_PATH.'/modules/'.$is_section['module'].'/add.php');
		}
		
		// copy module settings per section
		switch( $is_section['module'] ) {
		
			case 'wysiwyg':
				/**
				 *	Recode for LEPTON-CMS 2
				 */
				$all = array();
				
				$database->execute_query(
					"SELECT `content`,`text` FROM `".TABLE_PREFIX."mod_wysiwyg` WHERE `section_id` = '".$from_section."'",
					true,
					$all
				);	 

				foreach($all as &$is_wysiwyg) {

					$database->build_and_execute(
						"update",
						TABLE_PREFIX."mod_wysiwyg",
						$is_wysiwyg,
						"section_id =".$section_id
					);

				}
				break;
				
			case 'form':
				/**
				 *	Form settings
				 */
				$fields = array(
					'header', 'field_loop', 'footer', 'email_to', 'email_from', 
					'email_subject', 'success_page', 'success_email_to', 
					'success_email_from', 'stored_submissions', 'success_email_fromname', 
					'success_email_subject', 'max_submissions', 'use_captcha'
				);
				
				$query = $database->build_mysql_query(
					'select',
					TABLE_PREFIX."mod_form_settings",
					$fields,
					"`section_id` = '".$from_section."'"
				);
				
				$all = array();
				$database->execute_query( 
					$query,
					true,
					$all
				);
				
				foreach($all as &$is_formsettings) {
					$database->build_and_execute(
						"update",
						TABLE_PREFIX."mod_form_settings",
						$is_formsettings,
						"section_id =".$section_id
					);
				}
				
				/**
				 *	Form fields
				 */
			
				$fields = array(
					'position', 'title', 'type', 'required', 'value', 'extra'
				);
				
				$query = $database->build_mysql_query(
					'select',
					TABLE_PREFIX."mod_form_fields",
					$fields,
					"`section_id` = '".$from_section."'"
				);
				
				$all = array();
				$database->execute_query(
					$query,
					true,
					$all
				);
				
				foreach($all as &$is_formfield) {
					$is_formfield['section_id'] = $section_id;
					$is_formfield['page_id'] = $page_id;
					
					$database->build_and_execute(
						"insert",
						TABLE_PREFIX."mod_form_fields",
						$is_formfield
					);
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
						$tbl_suffix = $section_id; // addslashes($is_formsettings['tbl_suffix']);
						$enum_start = addslashes($is_formsettings['enum_start']);
						
						$database->query("UPDATE ".TABLE_PREFIX."mod_mpform_settings SET header = '$header', field_loop = '$field_loop', footer = '$footer', email_to = '$email_to', email_from = '$email_from', email_fromname = '$email_fromname', email_subject = '$email_subject', email_text = '$email_text', success_page = '$success_page', success_text = '$success_text', submissions_text = '$submissions_text', success_email_to = '$success_email_to', success_email_from = '$success_email_from', success_email_fromname = '$success_email_fromname', success_email_text = '$success_email_text', success_email_subject = '$success_email_subject', stored_submissions = '$stored_submissions', max_submissions = '$max_submissions', heading_html = '$heading_html', short_html = '$short_html', long_html = '$long_html', email_html = '$email_html', uploadfile_html = '$uploadfile_html', use_captcha = '$use_captcha', upload_files_folder = '$upload_files_folder', date_format = '$date_format', max_file_size_kb = '$max_file_size_kb', attach_file = '$attach_file', upload_file_mask = '$upload_file_mask', upload_dir_mask = '$upload_dir_mask', upload_only_exts = '$upload_only_exts', is_following = '$is_following', tbl_suffix = '$tbl_suffix', enum_start = '$enum_start' WHERE section_id = '$section_id'");
				}
				
				$query = "SELECT * FROM ".TABLE_PREFIX."mod_mpform_fields WHERE section_id = '$from_section'";
				$get_formfield = $database->query($query);
				$results_table_field_ids = array(); 
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
						
						$results_table_field_ids[] = $database->db_handle->lastInsertId();
				}
				
				//	Create the "results"-table
				$results_table = TABLE_PREFIX . "mod_mpform_results_" . $section_id;
				
				$query_str = "CREATE TABLE `".$results_table."` ( ";
				$query_str .= "`session_id` VARCHAR(20) NOT NULL,";
				$query_str .= "`started_when` INT NOT NULL DEFAULT '0' ,";	// time when first form was sent to browser
				$query_str .= "`submitted_when` INT NOT NULL DEFAULT '0' ,";	// time when last form was sent back to server
				$query_str .= "`referer` VARCHAR( 255 ) NOT NULL  ";			// referer page

				foreach( $results_table_field_ids as $temp_id ) {
					$query_str .= ", `field" . $temp_id . "` TEXT NOT NULL";
				}

				$query_str .= ", PRIMARY KEY ( `session_id` ) )";

				$database->execute_query( $query_str );
				
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
	
	echo '<p>Done - new pageid= '.$page_id.'</p>';
	return $page_id;
}

function clone_subs($pagetoclone, $parent) {
	global $database;
	
	$subpages = array();
	$database->execute_query(
		"SELECT `page_title`,`page_id` FROM `".TABLE_PREFIX."pages` WHERE `parent` = '".$pagetoclone."' order by `position`",
		true,
		$subpages
	);
		
	if(count($subpages) > 0)	{
		foreach($subpages as &$page) {

			echo '<p>clonepage('.$page['page_title'].','.$parent.','.$page['page_id'].')</p>>';
			$newnew_page = clone_page($page['page_title'],$parent,$page['page_id']);
			
			echo '<p>clonesubs('.$page['page_id'].','.$newnew_page.')</p><hr />';
			clone_subs($page['page_id'],$newnew_page);
		}
	}
}

// Clone selected page
echo '<p>clonepage('.$title.','.$parent.','.$pagetoclone.')</p>';
$new_page = clone_page($title,$parent,$pagetoclone);
echo '<p>new_pageid='.$new_page.'</p><hr>';

// Check if we need to clone subpages?
if ($include_subs == '1') {
	echo '<p>cloning subs('.$pagetoclone.','.$new_page.')</p><hr>';
	clone_subs($pagetoclone,$new_page);
}
	
// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(),'tool_clone.php?pagetoclone='.$pagetoclone);
} else {
	$admin->print_success($MESSAGE['PAGES']['ADDED'], ADMIN_URL.'/admintools/tool.php?tool=pagecloner');
}

$admin->print_footer();
