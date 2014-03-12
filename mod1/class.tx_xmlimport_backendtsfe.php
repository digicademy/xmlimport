<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Torsten Schrade <schradt@uni-mainz.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class "tx_xmlimport_backendtsfe"
 * 
 *  creates an instance of TSFE in the backend; needed for working with TypoScript cObj from PageTSConfig
 *  see: http://lists.typo3.org/pipermail/typo3-english/2011-January/072655.html
 *
 * @author		Torsten Schrade <schradt@uni-mainz.de>
 * @package		TYPO3
 * @subpackage	tx_xmlimport
 *
 * $Id$
 */

define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');

class tx_xmlimport_backendtsfe {

	/* $pid is the current import page
	 * 
	 */
	function buildTSFE($pageId = 1) {

			// classes needed for TSFE
		require_once(PATH_t3lib.'class.t3lib_timetrack.php');
		require_once(PATH_t3lib.'class.t3lib_tsparser_ext.php');
		require_once(PATH_t3lib.'class.t3lib_page.php');
		require_once(PATH_t3lib.'class.t3lib_stdgraphic.php');
		require_once(PATH_tslib.'class.tslib_fe.php');
		require_once(PATH_tslib.'class.tslib_content.php');
		require_once(PATH_tslib.'class.tslib_gifbuilder.php');

			// begin
		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_timeTrack');
			$GLOBALS['TT']->start();
		}

		if (!is_object($GLOBALS['TSFE']) && $pageId) {

				// builds TSFE object
			$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe',$GLOBALS['TYPO3_CONF_VARS'],$pageId,0,0,0,0,0,0);

				// builds sub objects
			$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
			$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
			$GLOBALS['TSFE']->page = $GLOBALS['TSFE']->sys_page->getPage($pageId);

				// init template
			$GLOBALS['TSFE']->tmpl->tt_track = 0; // Do not log time-performance information
			$GLOBALS['TSFE']->tmpl->init();
			$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($pageId);

				// generates the constants/config + hierarchy info for the template.
			$GLOBALS['TSFE']->tmpl->runThroughTemplates($rootLine,$template_uid);
			$GLOBALS['TSFE']->tmpl->generateConfig();
			$GLOBALS['TSFE']->tmpl->loaded = 1;

				// builds a cObj
			$GLOBALS['TSFE']->newCObj();
		}
	}
}
?>