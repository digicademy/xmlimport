<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "xmlimport".
 *
 * Auto generated 12-02-2013 19:44
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'XML Import',
	'description' => 'Import any XML data into TYPO3 tables using TypoScript, XPATH and XSLT',
	'category' => 'Digitale Akademie',
	'author' => 'Torsten Schrade',
	'author_email' => 'Torsten.Schrade@adwmainz.de',
	'shy' => '',
	'dependencies' => 'cobj_xpath,cobj_xslt',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => '',
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Academy of Sciences and Literature | Mainz',
	'version' => '0.5.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.5.0-6.0.99',
			'cobj_xpath' => '',
			'cobj_xslt' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:21:{s:16:"ext_autoload.php";s:4:"07c9";s:21:"ext_conf_template.txt";s:4:"6431";s:12:"ext_icon.gif";s:4:"f057";s:17:"ext_localconf.php";s:4:"5b29";s:14:"ext_tables.php";s:4:"e0b2";s:14:"ext_tables.sql";s:4:"f8a8";s:8:"TODO.txt";s:4:"c9e8";s:13:"doc/ChangeLog";s:4:"f9f3";s:14:"doc/manual.sxw";s:4:"469f";s:14:"doc/README.txt";s:4:"9942";s:12:"doc/TODO.txt";s:4:"413e";s:39:"mod1/class.tx_xmlimport_backendtsfe.php";s:4:"069b";s:38:"mod1/class.tx_xmlimport_extraction.php";s:4:"6e0e";s:13:"mod1/conf.php";s:4:"3ce0";s:14:"mod1/index.php";s:4:"4bb9";s:18:"mod1/locallang.xml";s:4:"d7f7";s:22:"mod1/locallang_csh.xml";s:4:"ea81";s:22:"mod1/locallang_mod.xml";s:4:"2f72";s:12:"mod1/mod.css";s:4:"1fe9";s:22:"mod1/mod_template.html";s:4:"28f6";s:19:"mod1/moduleicon.gif";s:4:"f057";}',
	'suggests' => array(
	),
);

?>