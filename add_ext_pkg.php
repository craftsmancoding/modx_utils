<?php
/**
 * Use this script to add your extension package to MODX's "radar".
 * This should only need to be done once.
 * Note that we have to instantiate MODX: xPDO is not sufficient
 * because we're running functions that exist only in MODX, not in the 
 * underlying xPDO framework.
 *
 * USAGE:
 * 1. Copy this file into the docroot (web root) of your MODX installation.
 * 2. Execute the file by visiting it in a browser, e.g. http://yoursite.com/add_extension.php
 */
//------------------------------------------------------------------------------
//! CONFIGURATION
//------------------------------------------------------------------------------
// Your package shortname:
$package_name = '';
 
//------------------------------------------------------------------------------
//  DO NOT TOUCH BELOW THIS LINE
//------------------------------------------------------------------------------
define('MODX_API_MODE', true);
require_once('index.php');
if (!defined('MODX_CORE_PATH')) {
    print '<p>MODX_CORE_PATH not defined! Did you put this script in the web root of your MODX installation?</p>';
    exit;
}
 
$modx= new modX();
$modx->initialize('mgr');
 
$modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
 
$modx->addExtensionPackage($package_name,"[[++core_path]]components/$package_name/model/");
 
print 'Success!';
