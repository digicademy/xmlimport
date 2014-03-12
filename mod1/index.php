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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class  tx_xmlimport_module1 extends t3lib_SCbase
 *   93:     public function init()
 *  104:     public function menuConfig()
 *  120:     public function getButtons()
 *  154:     public function printContent()
 *  164:     public function main()
 *  286:     protected function moduleContent()
 *  462:     protected function getData()
 *  531:     protected function setData()
 *  540:     protected function displaySingleRecord($record)
 *  639:     protected function showSubmitForm()
 *  708:     protected function processXMLdata($file)
 *  769:     protected function performImport($record)
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$LANG->includeLLFile('EXT:xmlimport/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

require_once(PATH_t3lib . 'class.t3lib_tceforms.php');
require_once(PATH_t3lib . 'class.t3lib_tcemain.php');
require_once(PATH_t3lib . 'class.t3lib_parsehtml.php');

/**
 * Module 'XML Import' for the 'xmlimport' extension.
 *
 * @author	Torsten Schrade <Torsten.Schrade@adwmainz.de>
 * @package	TYPO3
 * @subpackage	tx_xmlimport
 */
class tx_xmlimport_module1 extends t3lib_SCbase {

	public $pageinfo;					// current page in BE
	public $hookObjectsArr = array();	// array with classnames for hooks
	public $conf = array();				// configuration for the module
	public $params = array();			// incoming parameters
	public $currentRecord = array();	// current record for import
	public $newUids = array();			// new uids for records generated during import
	public $content;					// the content of the module
	public $extconf=array();			// extension configuration from EM
	public $keys=array();				// array with all index values from XML
	
	public $prevKey;					// previous index in import stack
	public $currentKey;					// current index in import stack
	public $nextKey;					// next index in import stack

	protected $cacheInstance;			// t3lib_cache_frontend_AbstractFrontend
	protected $registry;				// t3lib_Registry
	protected $tceforms;				// t3lib_TCEforms






####### BASIC MODULE INIT ###########






	/**
	 * 
	 */
	public function __construct() {

			// initialize cache
		$this->cacheInitialize();

			// initialize registry
		$this->registry = t3lib_div::makeInstance('t3lib_Registry');

		// TCEFORMS for record editing
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEmode();
		$this->tceforms->formName = 'txxmlimportM1';
	}

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function init()	{

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	public function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array		all available buttons as an assoc. array
	 */
	public function getButtons()	{

		$buttons = array(
			'csh' => '',
			'record_list' => '',
			'history_page' => '',
			'shortcut' => '',
		);

			// csh for module
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txxmlimportM1', '', $GLOBALS['BACK_PATH']);

			// If access to Web>List for user, then link to that module.
		if ($GLOBALS['BE_USER']->check('modules','web_list')) {
			$href = $BACK_PATH . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">'.'<img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/list.gif').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1).'" alt="" />'.'</a>';
		}

			// page history
		$buttons['history_page'] = '<a href="#" onclick="'.htmlspecialchars('jumpToUrl(\''.$BACK_PATH.'show_rechis.php?element='.rawurlencode('pages:'.$this->id).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).'#latest\');return false;').'">'.'<img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/history2.gif', 'width="13" height="12"').' vspace="2" hspace="2" align="top" title="'.$GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:recordHistory', 1).'" alt="" />'.'</a>';

			// shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	public function printContent()	{
			// compile markers
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

			// Build <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $this->getButtons(), $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}






####### APPLICATION LOGIC ###########






	/**
	 * Main function of the module.
	 *
	 * @return	void
	 */
	public function main()	{

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// get general settings
		$this->extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['xmlimport']);

			// get module settings: XML
		if ($this->modTSconfig['properties']['source.']['entryNode']) $this->conf['entryNode'] = $this->modTSconfig['properties']['source.']['entryNode'];
		if ($this->modTSconfig['properties']['source.']['entryNode.']) {
			$this->conf['entryNode'] = array();
			$this->conf['entryNode']['content'] = $this->modTSconfig['properties']['source.']['entryNode'];
			$this->conf['entryNode']['conf'] = $this->modTSconfig['properties']['source.']['entryNode.'];
		}

			// get import tables/fields configuration and match it to $TCA
		if (is_array($this->modTSconfig['properties']['destination.'])) {
			$this->conf['importConfiguration'] = $this->validateImportConfiguration($this->modTSconfig['properties']['destination.']);
		} else {
			$this->conf['importConfiguration'] = '';
		}

			// get params
		$this->params['action'] = (int) t3lib_div::_GP('action');
		$this->params['function'] = (int) $this->MOD_SETTINGS['function'];
		$this->params['key'] = (int) t3lib_div::_GP('key');
		$this->params['postVars'] = t3lib_div::_POST();
		$this->params['getVars'] = t3lib_div::_GET();
		$this->params['flush'] = (int) t3lib_div::_GET('flush');

			// module cmd to execute
		if (is_array(t3lib_div::_GP('cmd')) && count(t3lib_div::_GP('cmd')) == 1) {	
			$this->params['cmd'] = (string) key(t3lib_div::_GP('cmd'));
		} else {
			$this->params['cmd'] = (string) t3lib_div::_GP('cmd');
		}

			// directories & files
		if ($this->modTSconfig['properties']['source.']['directory']) $this->conf['directory'] = $this->modTSconfig['properties']['source.']['directory'];
		$this->conf['directory'] = t3lib_div::getFileAbsFileName($this->conf['directory']);
		if (t3lib_div::getFileAbsFileName($this->modTSconfig['properties']['source.']['file'])) $this->conf['file'] = $this->modTSconfig['properties']['source.']['file'];
		if (t3lib_div::getFileAbsFileName($this->modTSconfig['properties']['general.']['cssFile'])) $this->conf['cssFile'] = t3lib_div::getFileAbsFileName($this->modTSconfig['properties']['general.']['cssFile']);

			// general settings
		if ((int) $this->modTSconfig['properties']['general.']['noEdit'] == 1) $this->conf['noEdit'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['debug'] == 1) $this->conf['debug'] = 1;			
		if ((int) $this->modTSconfig['properties']['general.']['displayImportButton'] == 1) $this->conf['displayImportButton'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['displayReloadButton'] == 1) $this->conf['displayReloadButton'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['noBatchImport'] == 1) $this->conf['noBatchImport'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['submitForm.']['noUploadField'] == 1) $this->conf['submitForm']['noUpload'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['submitForm.']['noSelectField'] == 1) $this->conf['submitForm']['noFileSelection'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['recordBrowser.']['enable']) $this->conf['recordBrowser']['enable'] = 1;
		if ((int) $this->modTSconfig['properties']['general.']['recordBrowser.']['stepSize']) $this->conf['recordBrowser']['stepSize'] = (int) $this->modTSconfig['properties']['general.']['recordBrowser.']['stepSize'];
		if (is_array($this->modTSconfig['properties']['general.']['submitForm.']['limitOptions.'])) $this->conf['limitOptions'] = $this->modTSconfig['properties']['general.']['submitForm.']['limitOptions.'];
		if ((int) t3lib_div::_POST('limit') > 0) {
			$this->conf['limit'] = (int) t3lib_div::_POST('limit');
		} elseif ((int) $this->modTSconfig['properties']['general.']['limit'] > 0) {
			$this->conf['limit'] = (int) $this->modTSconfig['properties']['general.']['limit'];
		}

			// cache lifetime
		if (isset($this->modTSconfig['properties']['general.']['cacheLifetime'])) {
			$this->conf['cacheLifetime'] = (int) $this->modTSconfig['properties']['general.']['cacheLifetime'];
		} else { 
			$this->conf['cacheLifetime'] = (int) $this->extConf['tx_xmlimportM1.']['general.']['cacheLifetime'];
		}

			// register hook objects (if any)
		$this->hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['xmlimport/mod1/index.php']['xmlimportHookClass']))	{
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['xmlimport/mod1/index.php']['xmlimportHookClass'] as $classRef)	{
				$this->hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Access check: The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// initialize doc
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('xmlimport') . 'mod1/mod_template.html');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

				// Draw the form
			$this->doc->form = '<form action="mod.php?M=web_txxmlimportM1&amp;id='.$this->id.'" method="post" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" name="txxmlimportM1" id="txxmlimportM1" onsubmit="return TBE_EDITOR.checkSubmit(1);">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

				// include CSS and custom CSS files for BE module
			$relPath = str_replace(PATH_site, '', t3lib_extMgm::extPath('xmlimport'));
			$this->doc->addStyleSheet('xmlimport_module1', '../'.$relPath.'mod1/mod.css');
			if ($this->conf['cssFile']) {
				$cssFile = str_replace(PATH_site, '', $this->conf['cssFile']);
				$this->doc->addStyleSheet('xmlimport_user', '../'.$cssFile);
			}		

				// Render module content:
			if (!$this->conf['importConfiguration'] || !$this->conf['entryNode']) $missingconfig = 1;
			
			if (!$this->id) {
				$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.idFirst'), '', t3lib_FlashMessage::WARNING);
				t3lib_FlashMessageQueue::addMessage($message);
			} elseif ($this->id && $missingconfig) {
				$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.importConfigurationError'), '', t3lib_FlashMessage::ERROR);
				t3lib_FlashMessageQueue::addMessage($message);
			} else {
				$this->moduleContent();
			}

		} else {

				// If no access or if ID == zero
			$this->content .= $this->doc->spacer(10);

			$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.idFirst'), '', t3lib_FlashMessage::WARNING);				
			t3lib_FlashMessageQueue::addMessage($message);
		}
	}

	/**
	 * Implements the application logic and generates the module output
	 *
	 * @return	void
	 */
	protected function moduleContent()	{

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// function 1: edit and import data
		if ($this->params['function'] == 1) {

			switch ($this->params['action']) {

					// extraction and preview
				case 1:

						// function header
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('importSingle'), $content, 0, 1);

						// test key registry and record cache for current page
					$this->cacheLoadAndCleanRegistry();

						// if records have been extracted $this->keys will be filled - decide on cmd what to do
					if (is_array($this->keys) === TRUE && array_key_exists('cmd', $this->params) === TRUE) {

						switch ($this->params['cmd']) {

								// import happens as soon as the import button is clicked
							case 'import':

									// fetch data
								$this->getData($this->params['key']);

									// import the record, destroy the current element in stack and to move on to the next record
								$this->performImport($this->currentRecord);

									// remove record from cache
								$this->cacheRemoveSingle($this->params['key']);

									// should any errors occur during import, keep current record identifier
								$recordIdentifier = $this->keys[$this->params['key']];

									// update the $key index
								unset($this->keys[$this->params['key']]);
								$this->keys = array_values($this->keys);
								array_unshift($this->keys, 'x');
								unset($this->keys[0]);

									/* make sure that $this->nexKey doesn't point beyond remaining key stack; example: if there are three remaining records left
									 * and record two is imported above, then only two records remain but $this->nextKey still points to 3
									 */
								if (!$this->errorMsgs && $this->nextKey > count($this->keys)) $this->nextKey = '';

									// update key registry
								$this->registry->set('tx_xmlimport_M1', $this->id, $this->keys);

									// check that the cache lifetime is still valid 
								$this->cacheLoadAndCleanRegistry();
								if (is_array($this->keys) === FALSE) {
									$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.cacheHasExpired'), '', t3lib_FlashMessage::WARNING);
									t3lib_FlashMessageQueue::addMessage($message);
									$content .= $this->showSubmitForm();
									break;
								}

									// if there are TCEMAIN errors, turn back the changes from above and display the imported record again and the error messages
								if (count($this->errorMsgs) > 0) {

										// insert the imported element identifier back into the import stack
									if (count($this->keys) > 0) {
										$reindex = array();
										foreach ($this->keys as $k => $v) {
											if ($k == $this->params['key']) $reindex[] = $recordIdentifier;
											$reindex[] = $v; 
										}
											// if the array key doesnt't exist, import direction was moving backwards and it has to be appended to the end of the stack
										if (array_key_exists($this->params['key'], $reindex) === FALSE) $reindex[$this->params['key']] = $recordIdentifier;
										array_unshift($reindex, 'x');
										unset($reindex[0]);
										$this->keys = $reindex;
									// if this is the last remaining record in the stack, make sure that $this->keys is set at all
									} else {
										$this->keys[1] = $recordIdentifier;
									}

										// now update registry and cache
									$this->registry->set('tx_xmlimport_M1', $this->id, $this->keys);
									$this->cacheInsertSingle($this->params['key'], $this->currentRecord);

										// display the errors from TCEMAIN
									foreach ($this->errorMsgs as $error) {
										$message = t3lib_div::makeInstance('t3lib_FlashMessage', $error, '', t3lib_FlashMessage::ERROR);
										t3lib_FlashMessageQueue::addMessage($message);
									}

										// re-fetch the record for display
									$this->getData($this->params['key']);

										// prepare to show it again
									$content .= '
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
									';

										// buttons top
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

										// show
									$content .= $this->displaySingleRecord($this->currentRecord);

										// insert hidden fields
									$content .= $this->displayInsertHiddenFields($this->currentKey);

									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

									// record successfully imported, show next record
								} else {

										// display infos
									$importCount = count($this->keys);
									$content .= '<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>';

										// stop if the last record has been imported
									if (!$importCount) {
										$content .= '<p>'.$GLOBALS['LANG']->getLL('importComplete').'</p>';
										break;
									}

										// buttons top
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

										// get the next record for display
									if ($this->params['key'] === 1) {
											// begin of import stack - next key stays at 1
										$this->getData(1);
										$content .= '<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>';
										$content .= $this->displaySingleRecord($this->currentRecord);
									} elseif ($this->nextKey) {
											// somewhere in between - show the next record in stack (which will now be at the same position in the key stack as the imported record)
										$this->getData($this->currentKey);
										$content .= '<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>';
										$content .= $this->displaySingleRecord($this->currentRecord);
										// end of import stack - show previous record
									} else {
										$this->getData($this->prevKey);
										$content .= '<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>';
										$content .= $this->displaySingleRecord($this->currentRecord);
									}

										// hidden fields
									$content .= $this->displayInsertHiddenFields($this->currentKey);

										// buttons bottom
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);
								}

							break;

								// edit happens when an edit link is clicked or when editet content is resubmitted
							case 'edit':

									// fetch data
								$this->getData($this->params['key']);

									// case when edited content is resubmitted
								if ($this->params['postVars']['cmd'] == 'edit') {

										// assign the edited value to the record : $t = table, $i = index, $f = field
									$t = key($this->params['postVars']['data']);
									$i = key($this->params['postVars']['data'][$t]);
									$f = key($this->params['postVars']['data'][$t][$i]);

										/* RTE transformations before sending to cache - in case RTE was loaded, post data will contain the '_TRANSFORM_' flag first
										 * for this stuff check t3lib_tcemain line 1154ff. & 2443ff., t3lib_rteapi line 147ff.
										 */
										// XMLIMPORT TODO: no support for RTE file/image edit at the moment
									if (strpos($f, '_TRANSFORM_') !== FALSE) {
										$f = str_replace('_TRANSFORM_', '', $f);
										$currentRecord = array($f => $dataToTransform);
										$dataToTransform = $this->params['postVars']['data'][$t][$i][$f];

											// get RTE config
										$types_fieldConfig = t3lib_BEfunc::getTCAtypes($t, $currentRecord, 1);
										$theTypeString = t3lib_BEfunc::getTCAtypeValue($t, $currentRecord);
										$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', t3lib_BEfunc::getPagesTSconfig($this->id));
										$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'], $t, $f, $theTypeString);

											// Get RTE object and do transformation
										$RTEobj = t3lib_BEfunc::RTEgetObj();
										if (is_object($RTEobj)) {
											$this->currentRecord[$t][$i][$f] = $RTEobj->transformContent('db', $dataToTransform, $t, $f, $currentRecord, $types_fieldConfig[$f]['spec'], $thisConfig, $RTErelPath, $this->id);
										} else {
											debug('NO RTE OBJECT FOUND!');
										}

										// submitted data from non RTE fields
									} else {
										$this->currentRecord[$t][$i][$f] = $this->params['postVars']['data'][$t][$i][$f];
									}

										// count
									$content .= '
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
									';

										// buttons top
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

										// show changed record
									$content .= $this->displaySingleRecord($this->currentRecord);

										// hidden fields
									$content .= $this->displayInsertHiddenFields($this->currentKey);

										// buttons bottom
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

										// recache with changes
									$this->setData($this->currentKey);

									// case when a field is displayed in edit mode without import/reload buttons
								} else {

										// count
									$content .= '
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
									';

										// show - displaySinlgeRecord has to be called first, since it's tceforms excecution adds JS (e.g. for RTE) that has to be inserted at the top of the editform
										// transformation of content from db/cache -> RTE need not be taken care of since this happens in drawRTE() function of t3lib_rteapi line 113ff. 
									$editRecord = $this->displaySingleRecord($this->currentRecord);
									$content .= $this->tceforms->printNeededJSFunctions_top();
									$content .= $editRecord;

										// hidden fields
									$content .= $this->displayInsertHiddenFields($this->currentKey);

								}

									// include necessary JS at the bottom of the form/field
								$content .= $this->tceforms->printNeededJSFunctions();

							break;

								// current record will be re-extracted from XML stored within the record data (NOT from file/source...)
							case 'reload':

									// fetch data to get the original XML of the record
								$this->getData($this->params['key']);

									// reprocess current record
								$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
								$tmpEntryNode = $this->conf['entryNode'];
								$this->conf['entryNode'] = $htmlParser->getFirstTagName($this->currentRecord['###XML###'][0]['source'], 1);
								$record = $this->processXMLdata($this->currentRecord['###XML###'][0]['source']);
								$this->conf['entryNode'] = $tmpEntryNode;
								$this->currentRecord = $record[1];
								$this->processMarkerFields();

									// display processed record
								$content .= '
									<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
									<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
								';

									// buttons top
								if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
								if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

									// show
								$content .= $this->displaySingleRecord($this->currentRecord);

									// hidden fields
								$content .= $this->displayInsertHiddenFields($this->currentKey);

									// buttons bottom
								if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
								if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

									// recache
								$this->setData($this->currentKey);

							break;

								// default happens when prev/next is clicked
							default:

									// fetch data
								$this->getData($this->params['key']);

								if ($this->currentRecord) {
										// display record
									$content .= '
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
										<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
									';

										// buttons top
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

										// show
									$content .= $this->displaySingleRecord($this->currentRecord);

										// hidden fields
									$content .= $this->displayInsertHiddenFields($this->currentKey);

										// buttons bottom
									if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
									if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);
								}
							break;
						}

						// if $this-keys is not filled but the param signals XML submission, perform an extraction
					} elseif (is_array($this->keys) === FALSE && $this->params['postVars']['submitForm'] == '1') {

							// get records from XML source (this only happens once)
						$extraction = $this->readXMLFromFile();

							// set current record to the first extracted record
						if ($extraction === TRUE) { 
							$this->getData(1);
							if ($this->currentRecord) {
									// display record
								$content .= '
								<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('remainingRecords').' '.count($this->keys).'</strong></p>
								<p style="margin-bottom: 1em;"><strong>'.$GLOBALS['LANG']->getLL('currentPosition').' '.$this->currentKey.'</strong></p>
								';

									// buttons top
								if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
								if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);

									// show
								$content .= $this->displaySingleRecord($this->currentRecord);

									// hidden fields
								$content .= $this->displayInsertHiddenFields($this->currentKey);

									// buttons bottom
								if ($this->conf['displayImportButton']) $content .= $this->displayImportButton($this->currentKey);
								if ($this->conf['displayReloadButton']) $content .= $this->displayReloadButton($this->currentKey);
							}
						}

						// no valid keys and records cache exist, display warning
					} else {
						$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.cacheHasExpired'), '', t3lib_FlashMessage::WARNING);
						t3lib_FlashMessageQueue::addMessage($message);
						$content .= $this->showSubmitForm();
					}

				break;

					// show the form for data submission or a note that there are still records to be imported
				default:

					if ($this->params['flush'] === 1) $this->cacheRemoveAll();

					$this->cacheLoadAndCleanRegistry();

					if ($this->keys) {

						$content .= $this->doc->section($GLOBALS['LANG']->getLL('importSingle'), $content, 0, 1);
						$content .= '
							<p>'.sprintf($GLOBALS['LANG']->getLL('queueCount'), count($this->keys)).'</p>
							<p>'.$GLOBALS['LANG']->getLL('wouldYouLike').'</p>
							<ul>
								<li style="float: left; margin: 1em 0.5em 1em 0"><a style="display: inline-block; padding: 5px 10px; color: #434343; background: #F6F6F6 -moz-linear-gradient(center top , #F6F6F6 10%, #D5D5D5 90%) center bottom repeat-x; border: 1px solid #7C7C7C; border-radius: 1px 1px 1px 1px; cursor: pointer;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id).'&action=1&key=1">'.$GLOBALS['LANG']->getLL('goOn').'</a></li>
								<li style="float: left; margin: 1em 0;"><a style="display: inline-block; padding: 5px 10px; color: #434343; background: #F6F6F6 -moz-linear-gradient(center top , #F6F6F6 10%, #D5D5D5 90%) center bottom repeat-x; border: 1px solid #7C7C7C; border-radius: 1px 1px 1px 1px; cursor: pointer;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id).'&flush=1">'.$GLOBALS['LANG']->getLL('flushQueue').'</a></li>
							</ul>
						';

					} else {
						$content .= $this->doc->section($GLOBALS['LANG']->getLL('importSingle'), $content, 0, 1);
						$content .= $this->showSubmitForm();
					}

				break;
			}

			// batch import
		} elseif ($this->params['function'] == 2) {

			$this->cacheLoadAndCleanRegistry();

			$content .= $this->doc->section($GLOBALS['LANG']->getLL('importBatch'), $content, 0, 1);

			switch ($this->params['action']) {

				case 1:
					
					if ($this->keys) {
						foreach($this->keys as $key => $records) {

								// fetch data
							$this->getData($key);

								// import the record, destroy the current element in stack and to move on to the next record
							$this->performImport($this->currentRecord);

### GO ON: ERROR HANDLING ###

								// remove record from cache
							$this->cacheRemoveSingle($key);

							unset($this->keys[$key]);
						}
							// import complete, remove registry
						$this->cacheLoadAndCleanRegistry();

						$content .= '<p>'.$GLOBALS['LANG']->getLL('importComplete').'</p>';
					}

				break;

				default:

					if ($this->keys) {

						$content .= '
						<p>'.sprintf($GLOBALS['LANG']->getLL('queueCount'), count($this->keys)).'</p>
						<p>'.$GLOBALS['LANG']->getLL('wouldYouLike').'</p>
						<ul>
						<li style="float: left; margin: 1em 0.5em 1em 0"><a style="display: inline-block; padding: 5px 10px; color: #434343; background: #F6F6F6 -moz-linear-gradient(center top , #F6F6F6 10%, #D5D5D5 90%) center bottom repeat-x; border: 1px solid #7C7C7C; border-radius: 1px 1px 1px 1px; cursor: pointer;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id).'&function=2&action=1&key=1">'.$GLOBALS['LANG']->getLL('doBatchImport').'</a></li>
						</ul>
						';

					} else {
						$content .= '<p>'.$GLOBALS['LANG']->getLL('pleasePerformExtractionFirst').'</p>';
					}
				break;
			}

			// if it ends up here, something went completely wrong
		} else {
			$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.generalerror'), '', t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);
		}

		// assign output
		$this->content .= $content;
	}






####### DATA STORAGE & RETRIVAL ###########




		
	/** 
	 * Fetches record either from cache or from source and sets it to $this->currentRecord.
	 * 
	 * @param		int		The position of the record in the import stack
	 *
	 * @return		void
	 */
	protected function getData($currentKey = 0) {

			// if there is an entry in the system registry for the current page, get the according record from cache
		if (($this->keys = $this->registry->get('tx_xmlimport_M1', $this->id)) && (int) $currentKey > 0) {

				// calculate current position in import stack
			if (count($this->keys) > 1) {
				$this->currentKey = $currentKey;
				(($this->currentKey-1) > 0) ? $this->prevKey = $this->currentKey-1 : $this->prevKey = FALSE;
				(($this->currentKey+1) <= count($this->keys)) ? $this->nextKey = $this->currentKey+1 : $this->nextKey = FALSE;
			} else {
				$this->currentKey = 1;
			}

				// get record from cache
			$cacheIdentifier = sha1('tx_xmlimport_M1_'.$this->id.'_'.$this->keys[$this->currentKey]);
			$this->currentRecord = $GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->get($cacheIdentifier);

				// check for any relations to the record that may exist in DB
			$this->processMarkerFields();

#		} else {

				// get records from source (this only happens once - until a new source is submitted)
#			$extraction = $this->readXMLFromFile();

				// set current record to the first extracted record
#			if ($extraction === TRUE) $this->getData(1);
		}

			// debug output
		if ($this->conf['debug'] == 1) debug($this->currentRecord, '$this->currentRecord');
	}

	/** 
	 * Puts current record to cache
	 * 
	 * @param	int		The current position of the record in the import stack
	 *
	 * @return	void
	 */
	protected function setData($currentKey = 0) {
			// insert single record to cache
		if ((int) $currentKey > 0) {
			$this->cacheInsertSingle($currentKey, $this->currentRecord);
			// issue error
		} else {
			throw new t3lib_error_Exception('Lost or no record cache key. The current record could not be stored', 1327927107);
		}
	}





####### CACHE FUNCTIONS ###########





	/**
	* Initialize cache instance to be ready to use; taken from http://wiki.typo3.org/Caching_framework
	*
	* @return void
	*/
	protected function cacheInitialize() {
		t3lib_cache::initializeCachingFramework();
		try {
			$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache');
		} catch (t3lib_cache_exception_NoSuchCache $e) {
			$this->cacheInstance = $GLOBALS['typo3CacheFactory']->create(
				'tx_xmlimport_recordcache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options']
			);
		}
	}

	/**
	 * 
	 */
	protected function cacheInsertAll($data=array()) {

		if (count($data > 0)) {

				// clear any remaining import cache for the page
			$this->cacheRemoveAll();

				// write all extracted records to extension cache as single entries - this avoids huge data packets
			foreach ($data as $key => $value) {
				$cacheIdentifier = sha1('tx_xmlimport_M1_'.$this->id.'_'.$key);
				$entry = $value;
				$tags = array(0 => 'tx_xmlimport_M1_' . $this->id);
				$lifetime = $this->conf['cacheLifetime'];
				$GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->set($cacheIdentifier, $entry, $tags, $lifetime);
			}

				// store $this->keys to system registry for later retrival & record access
			$this->keys = array_keys($data);
			array_unshift($this->keys, 'x');
			unset($this->keys[0]);
			$this->registry->set('tx_xmlimport_M1', $this->id, $this->keys);

		} else {
			throw new t3lib_error_Exception('No records submitted for storage into cache', 1327927770);
		}
	}

	/**
	 * 
	 */
	protected function cacheInsertSingle($key, $record) {
		$cacheIdentifier = sha1('tx_xmlimport_M1_'.$this->id.'_'.$this->keys[$key]);
		$tags = array(0 => 'tx_xmlimport_M1_'.$this->id);
		$lifetime = $this->conf['cacheLifetime'];
		$GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->set($cacheIdentifier, $record, $tags, $lifetime);
	}

	/**
	 * 
	 */
	protected function cacheRemoveAll() {
			// generally collect all cache garbage
		$GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->collectGarbage();
			// remove all records for a certain pid
		$tag = 'tx_xmlimport_M1_' . $this->id;
		$GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->flushByTag($tag);
		$this->registry->remove('tx_xmlimport_M1', $this->id);
	}

	/**
	 * 
	 */
	protected function cacheRemoveSingle($key) {
		$cacheIdentifier = sha1('tx_xmlimport_M1_'.$this->id.'_'.$this->keys[$key]);
		$GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->remove($cacheIdentifier);
	}

	/**
	 * 
	 */
	protected function cacheLoadAndCleanRegistry() {
			// check if there is a valid entry in the sys registry for the current page
		$this->keys = $this->registry->get('tx_xmlimport_M1', $this->id);
			// check if there is a valid record cache for the current page
		$cacheIdentifier = sha1('tx_xmlimport_M1_'.$this->id.'_'.$this->keys[1]);
		$cacheTrue = $GLOBALS['typo3CacheManager']->getCache('tx_xmlimport_recordcache')->get($cacheIdentifier);
			// if there is no valid cache present, purge the key registry and the cache
		if (is_array($cacheTrue) === FALSE) {
			$this->registry->remove('tx_xmlimport_M1', $this->id);
			$this->keys = '';
			$this->cacheRemoveAll();
		}
	}




####### XML EXTRACTION FUNCTIONS ###########




	/**
	 * 
	 */
	protected function readXMLFromFile() {

			// get file either from submit or selection, tsconfig option is already in the background
		if ($_FILES['upload_file']['name']) {

				// file allowed?
			if (t3lib_div::verifyFilenameAgainstDenyPattern($_FILES['upload_file']['name']) && $_FILES['upload_file']['type'] == 'text/xml') {

					// mv into typo3temp
				$this->conf['file'] = t3lib_div::upload_to_tempfile($_FILES['upload_file']['tmp_name']);

				// not allowed!
			} else {
				unset($this->conf['file']);
				$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.forbiddenFile'), '', t3lib_FlashMessage::ERROR);
				t3lib_FlashMessageQueue::addMessage($message);
			}
 
			// get file from selection
		} elseif ($this->params['postVars']['select_file']) {
			$this->conf['file'] = $this->params['postVars']['select_file'];
		}

			// no file
		if (!$this->conf['file']) {
			$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.noFile'), '', t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);
		}

			// extraction
		$fileContents = t3lib_div::getURL(t3lib_div::getFileAbsFileName($this->conf['file']));
		$xmlData = $this->processXMLdata($fileContents);

			// unlink upload file
		if (strpos($this->conf['file'], 'typo3temp')) t3lib_div::unlink_tempfile($this->conf['file']);

			// cache data if extraction worked
		if ($xmlData) {
			$this->cacheInsertAll($xmlData);
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	 * Extracts all specified tables/fieldnames from the submitted XMl data structure and converts them into a multidimensional array.
	 * Contains two hooks for working on the extracted array: preProcessXMLData and postProcessXMLData.
	 *
	 * @param	string		XML data
	 * 
	 * @return	array		Multidimensional array with all extracted "records" and their associative key/value pairs
	 */
	protected function processXMLdata($xmlData) {

			// if there is data
		if ($xmlData != '') {

				// pre process hook before extraction
			if (count($this->hookObjectsArr > 0)) {
				foreach ($this->hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj,'preProcessXMLData'))	{
						$hookObj->preProcessXMLData($xmlData, $this->conf['importConfiguration'], $this);
					}
				}
			}

				// extract values from XML into a tables/fields array
			$xmlArray = $this->extractRecordsFromXML($xmlData, $this->conf['entryNode'], $this->conf['importConfiguration']);

				// if the XML conversion worked
			if (is_array($xmlArray)) {

					// post process hook after extraction
				if (count($this->hookObjectsArr > 0)) {
					foreach ($this->hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj,'postProcessXMLData'))	{
							$hookObj->postProcessXMLData($xmlData, $this->conf['importConfiguration'], $this);
						}
					}
				}

				return $xmlArray;

			} else {
					// error messages already in queue from extraction function
				return;
			}

			// no file found
		} else {

				// issue error
			$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.noData'), '', t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);

			return;
		}
	}

	/**
	 * 
	 */
	protected function extractRecordsFromXML($xmlString, $entryNode, $configuration) {

			// initialize result array
		$xmlArray = array();

			// initialize xml to process
		libxml_use_internal_errors(true);
		$xml2Process = simplexml_load_string($xmlString);

		if ($xml2Process instanceof SimpleXMLElement) {

				// prepare for stdWrap supported field extraction
			$backendTSFE = t3lib_div::makeInstance('tx_xmlimport_backendtsfe');
			$backendTSFE->buildTSFE($this->id);

				// set method to retrieve the entry point
			(is_array($entryNode) && count($entryNode) > 0) ? $expression = $GLOBALS['TSFE']->cObj->stdWrap($entryNode['content'], $entryNode['conf']) : $expression = '//'.$entryNode;

				// if the query had at least one match
			if (count($xml2Process->xpath($expression)) > 0) {

					// loop through all occurrences of $entryNode (the XML 'records')
				$i = 1;
				foreach ($xml2Process->xpath($expression) as $index => $record) {

						// break if extraction limit is reached
					if ($this->conf['limit'] > 0 && $index == $this->conf['limit']) break;

						// store simpleXML as array into $cObj->data; array conversion from soloman at http://www.php.net/manual/en/book.simplexml.php
					$json = json_encode($record);
					$GLOBALS['TSFE']->cObj->data = json_decode($json, TRUE);
					$GLOBALS['TSFE']->cObj->data['xml'] = $record->asXML();

						// now move on to extract the tables/fields
					$extractedRecord = array();
					foreach ($configuration as $tables => $settings) {

						$tablename = substr($tables, 0, -1);

							/* for each configured table it is possible to extract multiple records from the XML. What is considered a record
							 * can either be specified as a tagname or as xpath query. It has to be ensured that there is at least one iteration
							 * for all specified fields, otherwise foreach below would fail. Proceed as following:
							 * - if a tag is set in .recordNode, ask for this
							 * - if a xpath query is defined in .recordNode.expression, use this instead
							 * - regardless of the query the result will always be set to at least 1 to ensure field extraction
							 */

						$recordQuery = '';
						$recordQueryResult = array();

							// if just a tag was defined, set xpath query - if recordNode was not set at all, set result to 1
						($settings['recordNode']) ? $recordQuery = '//'.$settings['recordNode'] : $recordQueryResult[0] = 1;

							// a xpath query was given, overide the $record query
						if ($settings['recordNode.']['expression']) $recordQuery = $settings['recordNode.']['expression'];

							// do the xpath query if there is an expression defined
						if ($recordQuery) $recordQueryResult = $record->xpath($recordQuery);

							// no result from xpath query ? issue an error and set result to 1
						if (!is_array($recordQueryResult) && FALSE == $recordQueryResult) {
							$recordQueryResult[0] = 1;
							$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.recordNodeNotFound').$recordQuery, '', t3lib_FlashMessage::ERROR);
							t3lib_FlashMessageQueue::addMessage($message);							
						}
						
						$skipIfEmptyFields = array();
						if ($configuration[$tables]['skipIfEmptyFields']) {
							$skipIfEmptyFields = t3lib_div::trimExplode(',', $configuration[$tables]['skipIfEmptyFields']);
						}

							// now extract the records/fields for the current table
						foreach ($recordQueryResult as $key => $result) {

							$GLOBALS['TSFE']->register['RECORD_NODE_ITERATION'] = $key;

							foreach ($settings['fields.'] as $field => $value) {
								$fieldname = substr($field, 0, -1);
								$content = $GLOBALS['TSFE']->cObj->cObjGetSingle('TEXT', $value);
								if (!$content && in_array($fieldname, $skipIfEmptyFields)) {
									continue;
								} else {
									$extractedRecord[$tablename][$key][$fieldname] = $content;
								}
							}
						}

							// .if TypoScript implementation for each table - remove the current table from this record completely if .if returns false
							// has to be done here to take into account any TSFE values that might have been created during field extraction
						if (isset($settings['if.'])) {
							$skipTable = $GLOBALS['TSFE']->cObj->checkIf($settings['if.']);
							if ($skipTable === 0) unset($extractedRecord[$tablename]);
						}
					}

						// set the XML data for later reloading of the record - remove any xml header set by asXML, we just want the record XML
					$recordXML = trim(str_replace('<?xml version="1.0"?>', '', $record->asXML()));
					$extractedRecord['###XML###'][0]['source'] = $recordXML;

						// put the exracted record into storage
					$xmlArray[$i] = $extractedRecord;

					$i++;
				}

			} else {
					// issue error
				$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.entryNodeNotFound').$expression, '', t3lib_FlashMessage::ERROR);
				t3lib_FlashMessageQueue::addMessage($message);
			}
		

			// handle XML errors
		} else {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				switch ($error->level) {
					case LIBXML_ERR_WARNING:
						$messageText = 'XML Warning '.$error->code.': '.trim($error->message) . ' / Line: '.$error->line.' / Column: '.$error->column;
						$message = t3lib_div::makeInstance('t3lib_FlashMessage', $messageText, '', t3lib_FlashMessage::WARNING);
						t3lib_FlashMessageQueue::addMessage($message);
					break;
					case LIBXML_ERR_ERROR:
					case LIBXML_ERR_FATAL:
						$messageText = 'XML Error '.$error->code.': '. trim($error->message) . ' / Line: '.$error->line.' / Column: '.$error->column;
						$message = t3lib_div::makeInstance('t3lib_FlashMessage', $messageText, '', t3lib_FlashMessage::ERROR);
						t3lib_FlashMessageQueue::addMessage($message);
					break;
				}
			}
			libxml_clear_errors();
			$xmlArray = array();
		}
		return $xmlArray;
	}





####### DB FUNCTIONS ###########





	/** 
	 * If any fields contains the UID or UID:table markers, make a check by identifiers if the record exists. 
	 * This becomes important in scenarios where cached records contain relations to records that already have been imported.
	 * 
	 * @return	void
	 */
	protected function processMarkerFields() {

		foreach ($this->currentRecord as $tablename => $records) {

				// exclude the XML source field
			if ($table === '###XML###') continue;

			if (isset($this->conf['importConfiguration'][$tablename.'.']['markerFields'])) {

				$markerFields = t3lib_div::trimExplode(',', $this->conf['importConfiguration'][$tablename.'.']['markerFields'], 1);

				foreach ($markerFields as $fieldname) {

					foreach ($records as $key => $record) {

						$uid = 0;
						$identifiers = '';
						$foreignTable = '';
						$foreignIdentifiers = '';
						$foreignRecord = array();

						if ($record[$fieldname] === '###UID###') {

							$identifiers = $this->conf['importConfiguration'][$tablename.'.']['identifiers'];

							if (isset($identifiers)) {
								$uid = $this->getUidByIdentifiers($record, $tablename, $identifiers);
								if ($uid > 0) {
									$this->currentRecord[$tablename][$key][$fieldname] = $uid;
								}
							}
						}

							// if the field contains the UID keyword, check if it exists in DB using the identifiers of the current table
						if (substr($record[$fieldname], 0, 7) === '###UID:') {

							$foreignTable = substr($record[$fieldname], 7, -3);
// @TODO: reflect on this
							$foreignUID = $this->currentRecord[$foreignTable][0]['uid'];
							if ($foreignUID > 0) {
								$this->currentRecord[$tablename][$key][$fieldname] = $foreignUID;
							}
						}
					}
				}

					// if a uid has been found RECACHE
				$this->cacheInsertSingle($this->currentKey, $this->currentRecord);
			}
		}
	}

	/**
	 * 
	 */
	protected function getUidByIdentifiers($record, $table, $identifiers) {
		$where = '';
		foreach ($identifiers as $identifier) {
				// all identifier fields MUST contain a value, otherwise return FALSE immediately
			if ($record[$identifier] !== '') {
					// if the current identifier is in itself a pointer to another record resolve it's value
				if (substr($record[$identifier], 0, 7) === '###UID:') {
					$foreignTable = substr($record[$identifier], 7, -3);
// @TODO: reflect on this
					$record[$identifier] = $this->currentRecord[$foreignTable][0]['uid'];
					if ($record[$identifier] < 1) return FALSE;
				}
				$where .= ' AND '.$identifier.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($record[$identifier], $table);
			} else {
				return FALSE;
			}
		}
		$where = substr($where, 4);
		$row = t3lib_befunc::getRecordRaw($table, $where, 'uid');
		if (count($row > 0))  {
			return $row['uid'];
		} else {
			return FALSE;
		}
	}



	/** 
	 * Inserts/updates the record. Uses TCEmain. Contains a hook "performImportHook" for final data manipulation before the XML record is imported
	 *
	 * @param		array		The extracted XML record to import
	 *
	 * @return		void
	 */
	protected function performImport($data) {

			// final data manipulation
		if (count($this->hookObjectsArr > 0)) {
			foreach ($this->hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj,'performImportHook')) {
					$hookObj->performImportHook($data, $this);
				}
			}
		}

			// clean any remaing uids from last records
		$this->newUids = array();

			// walk through each extracted table and import it's records
		foreach ($data as $table => $records) {

				// skip the XML source field
			if ($table == '###XML###') continue;

			foreach ($records as $index => $fields) {

					// if a uid is set in the field array, clean it - TCEmain expects this as key and not as field in the datamap
				$uid = 0;
				$new = 0;
				if (array_key_exists('uid', $fields)) {
					$uid = (int) $fields['uid'];
					unset($fields['uid']);
				}

					// if a uid existed, no other record identifiers are needed - just ensure that the record really exists in DB
				if ($uid > 0) {

					$row = t3lib_befunc::getRecordRaw($table, 'uid='.$uid, 'uid');
					if (count($row > 0)) $uid = $row['uid'];

					// otherwise use the specified identifiers for testing if the record already exists in DB
				} elseif (count($this->conf['importConfiguration'][$table.'.']['identifiers']) > 0) {

					$uid = $this->getUidByIdentifiers($fields, $table, $this->conf['importConfiguration'][$table.'.']['identifiers']);
				}

					// if there is no $uid by now, the record has to be considered as new
				if (!$uid) {
					$uid = 'NEW_'.uniqid('');
					$new = 1;
				}

					// set some internal values if the according fields exist for the current table
				$fieldsInDB = $GLOBALS['TYPO3_DB']->admin_get_fields($table);
				$tstamp = time();

					// last update field
				if (array_key_exists('tstamp', $fieldsInDB)) $fields['tstamp'] = $tstamp;

					// creation date
				if ($new && array_key_exists('crdate', $fieldsInDB)) $fields['crdate'] = $tstamp;

					// creation user
				if ($new && array_key_exists('cruser_id', $fieldsInDB)) $fields['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];

					/* If any field within the current record is still set to '###UID:table###', check if there is a uid stored in the newUids array
					 * This get's important in a scenario where several DB records are created from one XML record in one run.
					 */
				foreach ($fields as $key => $value) {
					if (substr($value, 0, 7) == '###UID:') {
						$tableToRelate = substr($value, 7, -3);
						if ($this->newUids[$tableToRelate] > 0) $fields[$key] = $this->newUids[$tableToRelate];
					}
				}

					// build datamap for import/update
				$datamap = array(
					$table => array(
						$uid => $fields,
					)
				);

					// initialize TCEmain
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->start($datamap, null);
				$tce->process_datamap();

					// check for errors
				if (count($tce->errorLog) != 0) $this->errorMsgs = $tce->errorLog;

					// retrieve and store any new uids that have been created in this run
				if ($new) {
					$this->newUids[$table] = $tce->substNEWwithIDs[$uid];
				}
			}
		}
	}

	/**
	 * Matches the submitted array to the $TCA of the target tables.
	 * Any levels/keys/values that are not in the $TCA field list are removed (unless specified otherwise with dontValidateTablename and dontValidateFields).
	 *
	 * @param	array		The configuration array to match
	 *
	 * @return	array		The array processed against $TCA
	 */
	protected function validateImportConfiguration($configuration) {

		global $LANG;

		if (is_array($configuration) && count($configuration) > 0) {

			foreach ($configuration as $key => $value) {

				$table = substr($key, 0, -1);

					// if the table is not explicitly excluded from validation load it's TCA
				$value['dontValidateTablename'] ? $GLOBALS['TCA'][$table]['columns'] = array() : t3lib_div::loadTCA($table);

				if (($value['dontValidateTablename'] == 1) || isset($GLOBALS['TCA'][$table])) {

					if (count($value['fields.']) > 0) {

							// identifier check
						if (isset($value['identifiers'])) {
							$configuration[$key]['identifiers'] = t3lib_div::trimExplode(',', $value['identifiers'], 1);
							foreach ($configuration[$key]['identifiers'] as $index => $identifier) {
								if (array_key_exists($identifier, $value['fields.']) || array_key_exists($identifier.'.', $value['fields.'])) {
									continue;
								} else {
									$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.identifierFieldError').$table.'.'.$identifier, '', t3lib_FlashMessage::ERROR);
									t3lib_FlashMessageQueue::addMessage($message);
									unset($configuration[$key]['identifiers'][$index]);
								}
							}
						}

							// field check
						foreach ($value['fields.'] as $name => $conf) {

							$fieldname = substr($name, 0, -1);

							if ((array_key_exists($fieldname, $GLOBALS['TCA'][$table]['columns'])) === FALSE && strpos($value['dontValidateFields'], $fieldname) === FALSE) {
									// if the field is not configured, take it out
								unset($configuration[$key]['fields.'][$name]);
									// put warning into flash message queue
								$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.fieldNotConfigured').$table.'.'.$fieldname, '', t3lib_FlashMessage::WARNING);
								t3lib_FlashMessageQueue::addMessage($message);
							}
						}

						// no fields defined for the table, take the table out of the configuration	
					} else {
							// if the table is not configured, take it out
						unset($configuration[$key]);
							// put warning into flash message queue
						$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.noFieldsConfigured').$table, '', t3lib_FlashMessage::WARNING);
						t3lib_FlashMessageQueue::addMessage($message);
					}

				} else {
						// if the table is not configured, take it out
					unset($configuration[$key]);
						// put warning into flash message queue
					$message = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLL('errmsg.tableNotConfigured').$table, '', t3lib_FlashMessage::WARNING);
					t3lib_FlashMessageQueue::addMessage($message);
				}
			}
		}

		if (empty($configuration)) {
				// put warning into flash message queue
			$message = t3lib_div::makeInstance('t3lib_FlashMessage',$GLOBALS['LANG']->getLL('errmsg.importConfigurationError'), '', t3lib_FlashMessage::ERROR);
			t3lib_FlashMessageQueue::addMessage($message);
		}

		return $configuration;
	}





####### DISPLAY FUNCTIONS ###########






	/** 
	 * Displays the current record for import from the array stack
	 *
	 * return		string		The record as HTML table
	 */
	protected function displaySingleRecord($data) {

			// data display hook
		if (count($this->hookObjectsArr > 0)) {
			foreach ($this->hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj,'displaySingleRecordHook'))	{
					$hookObj->displaySingleRecordHook($data, $this);
				}
			}
		}

			// record browser
		if (count($this->keys) > 1 && $this->conf['recordBrowser']['enable']) {
			$steps = $this->conf['recordBrowser']['stepSize'];
			$out .= '<ul id="txxmlimportM1_recordbrowser" style="margin: 1em 0; padding: 0; list-style: none;">';
			reset($this->keys);
			$out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;"><a style="color: blue;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&key='.key($this->keys)).'">'.$GLOBALS['LANG']->getLL('begin').'</a></li>';
			($this->prevKey) ? $out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;"><a style="color: blue;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&key='.$this->prevKey).'">'.$GLOBALS['LANG']->getLL('prev').'</a></li>' : $out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;">'.$GLOBALS['LANG']->getLL('prev').'</li>';
			foreach ($this->keys as $k => $v) {
				if ($k % $this->conf['recordBrowser']['stepSize'] == false) {
					$out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;"><a style="color: blue;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&key='.$k).'">'.$steps.'</a></li>';
					$steps = $steps + $this->conf['recordBrowser']['stepSize'];
				}
			}
			($this->nextKey) ? $out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;"><a style="color: blue;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&key='.$this->nextKey).'">'.$GLOBALS['LANG']->getLL('next').'</a></li>' : $out .= '<li style="float: left; margin: 0 1em 0 0; padding: 0;">'.$GLOBALS['LANG']->getLL('next').'</li>';;
			end($this->keys);
			$out .= '<li style="float: left; margin: 0; padding: 0;"><a style="color: blue;" href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&key='.key($this->keys)).'">'.$GLOBALS['LANG']->getLL('end').'</a></li>';
			$out .= '</ul>';
		}

			// render import content
		foreach ($data as $table => $records) {

			// don't display the xml source
			if ($table == '###XML###') continue;

			$tableLabel = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if (!$tableLabel) $tableLabel = $table;

			$out .= '
				<table id="'.$table.'" class="typo3-dblist" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin-bottom: 8px;">
				<thead>
				<tr class="t3-row-header" style="padding: 8px;"><td colspan="3">'.$tableLabel.' ('.$table.')</td></tr>
				<tr class="c-headLine" style="font-weight: bold;">
				<td class="col-title">'.$GLOBALS['LANG']->getLL('fieldname').'</th>
				<td class="col-title">'.$GLOBALS['LANG']->getLL('value').'</th>';

				if (!$this->conf['noEdit']) $out .= '<td class="col-title">'.$GLOBALS['LANG']->getLL('correction').'</th>';

			$out .= '</tr></thead><tbody>';

				// needed for edit mode
			reset($this->currentRecord);

				// build records
			foreach ($records as $index => $record) {

					// also provide the "original" record - the current one might have been modified for display by the hook above
					// needed for getting the right values in edit mode
				$originalRecord = $this->currentRecord[$table][$index];

				if (count($records) > 1) $out .=  '<tr><td colspan="3">'.$GLOBALS['LANG']->getLL('record').' '.$index.'</td>';

				foreach ($record as $field => $value) {

					$fieldLabel = $GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel($table, $field, '[|]'));
					if (!$fieldLabel) $fieldLabel = '['.$field.']';

						// label column
					$out .= '<tr class="db_list_normal" id="row_'.$field.'"><td class="col1" style="width: 15%; border-right: 1px solid #CDCDCD; padding-right: 8px;">'.$fieldLabel.'</td>';

						// either we're in edit mode for the field
					if ($this->params['getVars']['cmd'] == 'edit' && $this->params['getVars']['field'] == $table.'-'.$field.'-'.$index) {

							// swap uid for extension name - a little trick since TCEforms normally expects uid values for field generation
						$originalRecord['uid'] = $index;

						$out .= '
						<td class="col2" id="field-'.$field.'">
							'.$this->tceforms->getSoloField($table, $originalRecord, $field).'
							<input type="hidden" name="action" value="1" />
							<input type="hidden" name="cmd" value="edit" />
							<input type="hidden" name="key" id="key" value="'.$this->currentKey.'" />
						</td>';

							// edit column
						$out .= '<td class="col3" style="text-align: center;"><input type="submit" value="'.$GLOBALS['LANG']->getLL('submitCorrection').'" /></td></tr>';
	
							// reset the fake id
						unset($originalRecord['uid']);

						$edit = 1;

						// or just display the row
					} else {

							// stdWrap for field value preview
						if (is_array($this->conf['importConfiguration'][$table.'.']['fieldPreviewStdWrap.'])) {
							if (is_object($GLOBALS['TSFE']) === FALSE) {
								$backendTSFE = t3lib_div::makeInstance('tx_xmlimport_backendtsfe');
								$backendTSFE->buildTSFE($this->id);
							}
							$GLOBALS['TSFE']->register['CURRENT_PREVIEW_FIELD'] = $field;
							$GLOBALS['TSFE']->register['CURRENT_PREVIEW_VALUE'] = $value;
							$value = $GLOBALS['TSFE']->cObj->stdWrap($value, $this->conf['importConfiguration'][$table.'.']['fieldPreviewStdWrap.']);
						}

							// value column
						$out .= '<td class="col2" style="width: 80%;">'.$value.'</td>';

							// edit column - preparation to make it editable through TCEFORMS
						$fieldtype = $GLOBALS['TCA'][$table]['columns'][$field]['config']['type'];
						$out .= '<td class="col3" style="width: 5%; text-align: center; border-left: 1px solid #CDCDCD;">';
						$editlink = '';
						if ($BE_USER->user['admin'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $table.':'.$field)) $editlink .= '<a href="mod.php?M=web_txxmlimportM1&amp;id='.htmlspecialchars($this->id.'&action=1&cmd=edit&field='.$table.'-'.$field.'-'.$index.'&key='.$this->currentKey).'#field-'.$field.'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif').' title="'.$GLOBALS['LANG']->getLL('editData').'" alt="" /></a>';
						if ($this->conf['noEdit'] || $fieldtype == 'none' || $fieldtype == 'passthrough' || $fieldtype === NULL) $editlink = '';
						$out .= $editlink.'</td></tr>';
					}

						// needed for edit mode
					next($this->currentRecord);
				}
			}

			// finish table
			$out .= '</tbody></table>';
		}

		return $out;
	}

	/** 
	 * Shows the submit form for the XMl file/data
	 *
	 * return 		string		The HTML for the submit form
	 */
	protected function showSubmitForm() {

				// start form
			$content = '
			<fieldset>
			<legend style="font-weight: bold;">'.$GLOBALS['LANG']->getLL('submitLegend').'</legend>
			';

				// upload field
			if (!$this->conf['submitForm']['noUpload']) {
			$content .= '
			<div style="margin: 0.5em; padding: 0.5em;">
			<label for="upload_file" style="display: block; margin: 0.5em 0; padding: 0.5em; font-weight: bold; border: 1px solid #abb3bf; background: #B7BEC8;">'.$GLOBALS['LANG']->getLL('uploadFile').'</label>
			<input type="file" id="upload_file" name="upload_file" size="50" />
			</div>';
			}

				// files from fileadmin
			$filesToSelect = t3lib_div::getAllFilesAndFoldersInPath(array(), $this->conf['directory'], 'xml');
			if ($filesToSelect && !$this->conf['submitForm']['noFileSelection']) {
			$content .= '
			<div style="margin: 0.5em; padding: 0.5em;">
			<label for="select_file" style="display: block; margin: 0.5em 0; padding: 0.5em; font-weight: bold; border: 1px solid #abb3bf; background: #B7BEC8;">'.$GLOBALS['LANG']->getLL('selectFile').' ('.$GLOBALS['LANG']->getLL('directoryName').$this->conf['directory'].')</label>
			<select name="select_file" id="select_file">
			<option value="0">-</option>';
			foreach ($filesToSelect as $file) {
			$content .= '
			<option value="'.$file.'">'.substr($file, strrpos($file, '/')+1).'</option>
			';
			}
			$content .= '
			</select>
			</div>';
			}

				// file set from TS
			if ($this->modTSconfig['properties']['source.']['file']) {	
			$content .= '
			<div style="margin: 0.5em; padding: 0.5em;">
			<p style="margin: 0.5em 0; padding: 0.5em; font-weight: bold; border: 1px solid #abb3bf; background: #B7BEC8;">'.$GLOBALS['LANG']->getLL('standardFile').$this->conf['file'].'</p>
			</div>';
			}

				// what to do
			$content .= '
			<div style="margin: 0.5em; padding: 0.5em;">
			<label for="action" style="display:none">'.$GLOBALS['LANG']->getLL('action').'</label>
			<select name="action" id="action" style="display:none;">
			<option value="1">'.$GLOBALS['LANG']->getLL('action1').'</option>';
//			if (!$this->conf['noImport'] && !$this->conf['noBatchImport']) $content .= '<option value="2">'.$GLOBALS['LANG']->getLL('action2').'</option>';
			$content .= '
			</select>
			<label for="limit">'.$GLOBALS['LANG']->getLL('limit').'</label>
			<select name="limit" id="limit">
			<option value="0">'.$GLOBALS['LANG']->getLL('noLimit').'</option>';
			if (is_array($this->conf['limitOptions'])) {
			foreach ($this->conf['limitOptions'] as $option) {
				if ((int) $option > 0) $content .= '<option value="'.$option.'">'.$option.'</option>';
			}}
			$content .= '
			</select>
			</div>
			<div style="margin: 0.5em; padding: 0.5em;">';

				// submit button
			$content .= '
			<input type="hidden" name="key" id="key" value="1" />
			<input type="hidden" name="submitForm" id="submitForm" value="1" />
			<input type="submit" onclick="return checkall();" value="'.$GLOBALS['LANG']->getLL('submitButton').'" />
			</div>
			</fieldset>
			';

			return $content;
	}

	/** 
	 * Displays an import button below the record preview.
	 * 
	 * @param		int			key of the record to import
	 * 
	 * @return		string		HTML button
	 */
	protected function displayImportButton($key) {
		$content = '
			<input type="submit" name="cmd[import]" style="margin: 10px 0;" value="'.$GLOBALS['LANG']->getLL('importButton').'" />
		';
		return $content;
	}

	/** 
	 * Displays a reload button below the record preview.
	 * 
	 * @param		int			key of the current record
	 * 
	 * @return		string		HTML button
	 */
	protected function displayReloadButton($key) {	
		$content = '
			<input type="submit" name="cmd[reload]" style="margin: 10px 0;" value="'.$GLOBALS['LANG']->getLL('reloadButton').'" />
		';
		return $content;
	}

	/**
	 * 
	 */
	protected function displayInsertHiddenFields($key) {
		$content .= '
			<input type="hidden" name="action" value="1" />
			<input type="hidden" name="key" id="key" value="'.$key.'" />
		';
		return $content;
	}
}


########### MODULE FOOTER #############


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/xmlimport/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/xmlimport/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_xmlimport_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>