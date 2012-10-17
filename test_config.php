<?php
/*
THIS FILE IS IN PROGRESS...

Dammit all captain!  Is the config file working correctly?
This file MUST be accessed in a browser (no  command line access).

*/
define('MODX_API_MODE', true);

// Full path to the index
$path_to_index = 'index.php';


if (!isset($_GET['modx_test']) {
	header('?modx_test=1');
	exit;
}
else
	print 'testing...'
	exit;
}

//------------------------------------------------------------------------------
/* 
if (!isset($_GET['modx_test']) {

	$_GET['modx_test'] = 'root';
}
elseif ($_GET['modx_test'] == 'root') {
	$_GET['modx_test'] = 'manager';
}
elseif($_GET['modx_test'] == 'manager' {
	$_GET['modx_test'] = 'connectors';
}
elseif($_GET['modx_test'] == 'connectors' {

	unset($_GET['modx_test']);
	// print summary
}
*/

// set session
//
require_once($path_to_index);

//------------------------------------------------------------------------------
// Do not edit below this line.
//------------------------------------------------------------------------------

// collect the errors
$errors = array();

// Test $database_dsn.  
$what_dsn_should_be = "$database_type:host=$database_server;dbname=$dbase;charset=$database_connection_charset";
if($what_dsn_should_be != $database_dsn) {
	$errors[] = '$database_dsn is not set correctly.  It should read: '.$database_dsn;
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

// Classes?
// modX


// Test MODX_CORE_PATH
// Is this really the CORE_PATH?
// Should be folders for: cache, components, config, docs, error, export, import, lexicon, model, packages, xpdo

// Test MODX_PROCESSORS_PATH
// Should be folders for aws, modx, phpthumb, schema, smarty

// Test MODX_CONNECTORS_PATH
// Should be folders for browser, context, element, layout, resource, security, source, system, workspace

// Test MODX_MANAGER_PATH
// Folders for assets, controllers, min, templates

// Test config.core.php files
// 1. root 
// 2. manager
// 3. connectors
// 4. database: workspace
// For all, does MODX_CONFIG_KEY match?
// For all, does MODX_CORE_PATH match?
// see http://www.php.net/manual/en/function.runkit-constant-remove.php

// Test Database


// Summary



/*EOF*/