<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */
$extensionPath = t3lib_extMgm::extPath('xmlimport');
return array(
	'tx_xmlimport_backendtsfe' => $extensionPath . 'mod1/class.tx_xmlimport_backendtsfe.php',
);
?>