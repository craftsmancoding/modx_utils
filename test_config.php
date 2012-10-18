<?php
/**
 * SUMMARY:
 * This script runs a series of tests to ensure that your MODX Revolution website is configured
 * correctly. It was developed to test for the most common errors (mostly typos) that crop up
 * when installing or moving a site.
 * 
 * USAGE:
 * 1. Upload this script to the Revo website, e.g. to the docroot.
 * 2. Edit the $path_to_core if you put this script somewhere other than 
 *	alongside the MODX index.php file.
 * 3. Run the script, e.g. by visiting it in a browser: http://yoursite.com/test_config.php
 *	and use any reported error messages to help you clean up your site's configuration.
 * 4. After you verify that the new user has been created, delete this script from the site.
 *
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 * http://craftsmancoding.com/
 */

//------------------------------------------------------------------------------
// CONFIG
//------------------------------------------------------------------------------
// Path to the dir where the core lives, ends in trailing slash, e.g. '../' or '/home/user/core/'
// Leave blank if this script and the core are in the docroot.
$path_to_core = '';

//------------------------------------------------------------------------------
// DO NOT EDIT BELOW THIS LINE
//------------------------------------------------------------------------------

define('MODX_API_MODE', true);


function print_errors($e) {
	$error_list = implode('<br/>',$e);
	print '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
		<span style="color:red; font-weight:bold;">Error</span><br />
		<p>The following errors were detected:</p>'.$error_list.'</div>';
}

function print_success() {
	print '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>Your MODX installation appears to be set up correctly.  This is <em>not</em> a guarantee
		that your site is running correctly!  It means that your site is free from the bigger errors and 
		misconfigurations.</p>
	</div>';
}

if (!file_exists($path_to_docroot.'core/config/config.inc.php')) {
	$errors[] = 'Incorrect path to core!';
}

if (!empty($errors)) {
	print_errors($errors);
	exit;
}

require_once($path_to_docroot.'core/config/config.inc.php');

$errors = array();

// Test $database_dsn.  
$what_dsn_should_be = "$database_type:host=$database_server;dbname=$dbase;charset=$database_connection_charset";
if($what_dsn_should_be != $database_dsn) {
	$errors[] = '$database_dsn is not set correctly.  It should read: '.$what_dsn_should_be;
}

// Test the constants
if(!defined('MODX_CORE_PATH')) {
	$errors[] = 'MODX_CORE_PATH not defined!';
}
if(!defined('MODX_PROCESSORS_PATH')) {
	$errors[] = 'MODX_PROCESSORS_PATH not defined!';
}
if(!defined('MODX_CONNECTORS_PATH')) {
	$errors[] = 'MODX_CONNECTORS_PATH not defined!';
}
if(!defined('MODX_MANAGER_PATH')) {
	$errors[] = 'MODX_MANAGER_PATH not defined!';
}
if(!defined('MODX_MANAGER_URL')) {
	$errors[] = 'MODX_MANAGER_URL not defined!';
}
if(!defined('MODX_BASE_PATH')) {
	$errors[] = 'MODX_BASE_PATH not defined!';
}
if(!defined('MODX_URL_SCHEME')) {
	$errors[] = 'MODX_URL_SCHEME not defined!';
}
if(!defined('MODX_HTTP_HOST')) {
	$errors[] = 'MODX_HTTP_HOST not defined!';
}
if(!defined('MODX_SITE_URL')) {
	$errors[] = 'MODX_SITE_URL not defined!';
}
if(!defined('MODX_ASSETS_PATH')) {
	$errors[] = 'MODX_ASSETS_PATH not defined!';
}
if(!defined('MODX_ASSETS_URL')) {
	$errors[] = 'MODX_ASSETS_URL not defined!';
}
if(!defined('MODX_CACHE_DISABLED')) {
	$errors[] = 'MODX_CACHE_DISABLED not defined!';
}

// Stage 1
if (!empty($errors)) {
	print_errors($errors);
	exit;
}

require_once( MODX_CORE_PATH . 'model/modx/modx.class.php');

// Classes? : modX
if (!class_exists('modX')) {
	$errors[] = 'modX class not found! The modX class file should be located at '.MODX_CORE_PATH . 'model/modx/modx.class.php';
	print_errors($errors);
	exit;
}



// Test MODX_CORE_PATH
// Is this really the CORE_PATH?
// Should be folders for: cache, components, config, docs, error, export, import, lexicon, model, packages, xpdo
$folders = array('cache', 'components', 'config', 'docs', 'error', 'export', 'import', 'lexicon', 'model', 'packages', 'xpdo');
foreach ($folders as $f) {
	if(!file_exists(MODX_CORE_PATH.$f)) {
		$errors[] = MODX_CORE_PATH.$f.' folder does not exist!';
	}
}


// Test MODX_PROCESSORS_PATH
// Should be folders for 'browser', 'context', 'element', 'resource', 'security', 'source', 'system', 'workspace'
$folders = array('browser', 'context', 'element', 'resource', 'security', 'source', 'system', 'workspace');
foreach ($folders as $f) {
	if(!file_exists(MODX_PROCESSORS_PATH.$f)) {
		$errors[] = MODX_PROCESSORS_PATH.$f.' folder does not exist!';
	}
}

// Test MODX_CONNECTORS_PATH
// Should be folders for browser, context, element, layout, resource, security, source, system, workspace
$folders = array('browser', 'context', 'element', 'layout', 'resource', 'security', 'source', 'system', 'workspace');
foreach ($folders as $f) {
	if(!file_exists(MODX_CONNECTORS_PATH.$f)) {
		$errors[] = MODX_CONNECTORS_PATH.$f.' folder does not exist!';
	}
}

// Test MODX_MANAGER_PATH
// Folders for assets, controllers, min, templates
$folders = array('assets', 'controllers', 'min', 'templates');
foreach ($folders as $f) {
	if(!file_exists(MODX_MANAGER_PATH.$f)) {
		$errors[] = MODX_MANAGER_PATH.$f.' folder does not exist!';
	}
}

if (!empty($errors)) {
	print_errors($errors);
	exit;
}


// Test config.core.php files
// 1. root 
// 2. manager
// 3. connectors
if (!file_exists(MODX_BASE_PATH.'config.core.php')) {
	$errors[] = 'Missing configuration file at '.MODX_BASE_PATH.'config.core.php';
}
if (!file_exists(MODX_MANAGER_PATH.'config.core.php')) {
	$errors[] = 'Missing configuration file at '.MODX_MANAGER_PATH.'config.core.php';
}
if (!file_exists(MODX_CONNECTORS_PATH.'config.core.php')) {
	$errors[] = 'Missing configuration file at '.MODX_CONNECTORS_PATH.'config.core.php';
}

$root_conf = file_get_contents(MODX_BASE_PATH.'config.core.php');
$mgr_conf = file_get_contents(MODX_MANAGER_PATH.'config.core.php');
$conn_conf = file_get_contents(MODX_CONNECTORS_PATH.'config.core.php'); 

if ($root_conf != $mgr_conf || $root_conf != $conn_conf || $mgr_conf != $conn_conf) {
	$errors[] = 'The contents of your config.core.php files do not match.';
}

$modx = new modx();

if (!is_object($modx)) {
	$errors[] = 'Unable to instantiate modX object.';
	print_errors($errors);
	exit;
}

// Test Database
foreach($modx->query("SELECT NOW() as now") as $row) {
	if (!isset($row['now']) || empty($row['now'])) {
		$errors[] = 'Unable to issue database query.';
	}
	break;
}


// Check database: workspace
$Workspace = $modx->getObject('modWorkspace',1);
if (!is_object($Workspace)) {
	$errors[] = 'Error reading workspaces table.';
}
else {
	$path = $Workspace->get('path');
	if ($path != '{core_path}' && $path != MODX_CORE_PATH) {
		$errors[] = 'Workspace path does not match MODX_CORE_PATH';
	}
}

// Permissions
if (!is_writable(MODX_CORE_PATH.'cache')) {
	$errors[] = MODX_CORE_PATH.'cache directory is not writable!';
}
if (!is_writable(MODX_ASSETS_PATH.'components')) {
	$errors[] = MODX_ASSETS_PATH.'components directory is not writable!';
}

// Summary
if (!empty($errors)) {
	print_errors($errors);
	exit;
}
else {
	print_success();
}

/*EOF*/