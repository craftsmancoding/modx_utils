<?php
/**
 * SUMMARY:
 * This script tests a server 
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

$req_min_php_version = 5.4.13;

function print_errors($e) {
	$error_list = implode('<br/>',$e);
	print '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
		<span style="color:red; font-weight:bold;">MODX Cannot Run on this Server</span><br />
		<p>The following errors were detected:</p>'.$error_list.'</div>';
}

function print_success() {
	print '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>Your server seems to have what it takes to run MODX.  This does not guarantee that </p>
	</div>';
}
$errors = array();

// Test PHP version
if ( version_compare( phpversion(), $req_min_php_version, '<') ) {
    $errors[] = sprintf('MODX requires PHP %2$s or newer.', $req_min_php_version);
}

// Test for req'd modules
$ext = get_loaded_extensions();
// Summary
if (!empty($errors)) {
	print_errors($errors);
	exit;
}
else {
	print_success();
}

/*EOF*/
