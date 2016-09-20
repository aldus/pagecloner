#PageCloner
for LEPTON-CMS 2

## Description
This addon allows you to clone a page or a complete tree with all page and sections.
Copying of complete datasets from pagesections to their clones is limited to following 
modules: wywsiwyg, form, mpform, code, code2. 
Only the sections (with their default) settings are cloned for not supported modules.

### Authors
John Maats, Christian Sommer, Dietrich Roland Pehlke, Stephan Kuehn, vBoedefeld, cms-lab

### Changelog

- 1.2.1	cms-lab
  + Bugfix for "mpform"
  + Bugfixes for MySQL StrictMode
  + Serval codechanges for L* 2.2 and PHP 7
   
- 1.0.0 cms-lab
  + upload to Git
  + prepare for LEPTON 2.x  
  
- 0.5.6 cms-lab
  + recode for LEPTON 1.3.2   

- v 0.5.4 (Stephan K&uuml;hn; 10. August 2010)
  + mpform support
  + migrating the pagetree idea by pcwacht support 
        
- v 0.5.1 (Dietrich Roland Pehlke; 04. September, 2008)
  + add new modultype "code2" to tool_doclone.php. See comments at line 179 for details.
  + Minor cosmetic changes in tool_doclone.php
		
- v 0.5.0 (Christian Sommer; 05 Feb, 2008)
  + added support for the upcoming WB 2.7 version (this version works also with WB < 2.7)
	  (background: admin tools were moved from admin/settings to admin/admintools with WB 2.7)

- v 0.4.0 (John Maats; 08 Jan, 2006)
  + fixed bug (removed dutch debugging text from line 91-93 in 'tool_doclone.php)

- v 0.3.0 (John Maats; 08 Jan, 2006)
  + fixed bug (forgot block number copy in section db)

- v 0.2.0 (John Maats; 08 Jan, 2006)
  + added copy content/settings from modules code, wysiwyg and form

- v 0.1.0 (John Maats; 07 Jan, 2006)
  + initial release