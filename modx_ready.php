<?php
/**
 * SUMMARY:
 * This script tests whether a server is capable of running MODX.
 * See http://rtfm.modx.com/display/revolution20/Server+Requirements
 *
 * USAGE:
 * 1. Upload this script to the server where you want to run MODX, e.g. to the web root.
 * 2. Run the script, e.g. by visiting it in a browser: http://yoursite.com/modx_ready.php
 *	and use any reported error messages to help you to update your server..
 * 3. After you have cleaned up any configuration errors, delete this script from the site.
 *
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 * http://craftsmancoding.com/
 *
 * LAST UPDATED: 
 * 2013-03-10 MODX 2.2.6
 */

//------------------------------------------------------------------------------
// DO NOT EDIT BELOW THIS LINE
//------------------------------------------------------------------------------
$req_min_php_version = '5.1.2';
$req_ext = array('zlib','json','gd','PDO','pdo_mysql','curl','SimpleXML','mysql');

function print_errors($e) {
	$error_list = implode('<br/>',$e);
	print '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
		<span style="color:red; font-weight:bold;">MODX Cannot Run on this Server</span><br />
		<p>The following errors were detected:</p>'.$error_list.'
		
		<p><em>This test is not comprehensive and it may not adequately test your particular server environment.</em</p>
		</div>';
}

function print_success() {
	print '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>Your server seems to have what it takes to run MODX.  This does not guarantee that everything will
		work, but at least your server is not missing anything obvious.</p>
	</div>';
}
$errors = array();

// Test PHP version
if ( version_compare( phpversion(), $req_min_php_version, '<') ) {
    $errors[] = sprintf('MODX requires PHP %s or newer.', $req_min_php_version);
}
if ( version_compare( phpversion(), '5.1.6', '=') ) {
    $errors[] = 'MODX cannot run using PHP 5.1.6.';
}
if ( version_compare( phpversion(), '5.2.0', '=') ) {
    $errors[] = 'MODX cannot run using PHP 5.2.0.';
}

// Test for req'd modules
foreach ($req_ext as $e) {
    if (!extension_loaded($e)) {
        $errors[] = sprintf('MODX requires that PHP has the %s extension.', '<code>'.$e.'</code>');
    }
}

// Image Magick (optional)
if(!class_exists('Imagick')) {
    // $errors[] = 'MODX prefers to have ImageMagick installed for thumbnail generation.';
    print 'Could not find ImageMagick, but it is optional...  Skipping...<br/>';
}

// Test for mod_rewrite
if (function_exists('apache_get_modules')) {
    if (!in_array('mod_rewrite', apache_get_modules())) {
        $errors[] = 'MODX works best if Apache includes the <code>mod_rewrite</code> module.';
    }
}
else {
    print 'Could not test Apache modules.  Skipping...<br/>';
}

// Safe Mode off
if(ini_get('safe_mode')) {
    $errors[] = "MODX requires PHP's <code>safe_mode</code> to be disabled.";
}
// Globals off
if(ini_get('register_globals')) {
    $errors[] = "MODX requires PHP's <code>register_globals</code> to be disabled.";
}
// Magic quotes off
if(ini_get('magic_quotes_gpc') || function_exists('magic_quotes_gpc')) {
    $errors[] = "MODX requires PHP's <code>magic_quotes_gpc</code> to be disabled.";
}
// Memory Limit
$ram = ini_get('memory_limit');
$ram = preg_replace('/[^0-9]/', '', $ram); 
if ($ram < 24) {
    $errors[] = 'MODX requires at least 24M of memory.';
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
