<?php
if (!defined('TYPO3_MODE')) die('Not in Typo3');

// Register cache
if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache'] = array();
}
// Define string frontend as default frontend, this must be set with TYPO3 4.5 and below
// and overrides the default variable frontend of 4.6
if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['frontend'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['frontend'] = 't3lib_cache_frontend_VariableFrontend';
}
if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < '4006000') {
    // Define database backend as backend for 4.5 and below (default in 4.6)
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['backend'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['backend'] = 't3lib_cache_backend_DbBackend';
    }
    // Define data and tags table for 4.5 and below (obsolete in 4.6)
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options'] = array();
    }
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options']['cacheTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options']['cacheTable'] = 'tx_xmlimport_recordcache';
    }
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options']['tagsTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_xmlimport_recordcache']['options']['tagsTable'] = 'tx_xmlimport_recordcache_tags';
    }
}
?>