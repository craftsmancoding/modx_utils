<?php
/*
 * Put this script in the web root of a MODX site and then hit it in a browser:
 * it tests the configured database connection.
 */

function print_errors($e)
{
    $error_list = implode('<br/>', $e);
    print '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
		<span style="color:red; font-weight:bold;">Error</span><br />
		<p>Could not connect to your database. Check the following settings:</p>' . $error_list . '</div>';
}

function print_success()
{
    print '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>Your MODX database configuration is correct and PHP was able to connect to it.</p>
	</div>';
}


/**
 * Find config...
 * As long as this script is placed inside a MODX docroot, this will sniff out
 * the MODX config file that defines the database connection
 * (usually inside core/config/config.inc.php)
 */
function detect_modx_constants()
{
    if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
        $max = 10;
        $i = 0;
        $dir = dirname(__FILE__);
        while (true) {
            if (file_exists($dir . '/config.core.php')) {
                include $dir . '/config.core.php';
                break;
            }
            $i++;
            $dir = dirname($dir);
            if ($i >= $max) {
                print("Could not find a valid MODX config.core.php file.\n"
                    . "Make sure your repo is inside a MODX webroot and try again.");
                die(1);
            }
        }
    }
}

detect_modx_constants();
if (!file_exists(MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php')) {
    print_errors([MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php not found!']);
    die(3);
}

require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

//$dsn = "$database_type:host=$host;dbname=$dbname;port=$port;charset=$charset";
$xpdo = new xPDO($database_dsn, $database_user, $database_password);

// Test your connection
if ($xpdo->connect()) {
    print_success();
}
else {
    print_errors([
        '$database_type: '. $database_type . ' (should be mysql or postgres)',
        '$database_server: ' . $database_server. '(should include the hostname:port)',
        '$database_user: ' . $database_user,
        '$database_password: ' . $database_password,
        '$database_connection_charset: '. $database_connection_charset,
        '$dbase: ' .$dbase . ' (database name)',
        '$database_dsn: ' . $database_dsn
    ]);
}

// TODO: double-check with a mysqli connection
//mysqli_connect(host,username,password,dbname,port,socket)