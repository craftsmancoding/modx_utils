<?php
/*------------------------------------------------------------------------------
As posted on http://rtfm.modx.com/display/revolution20/Reverse+Engineer+xPDO+Classes+from+Existing+Database+Table
================================================================================
=== Reverse Engineer Existing MySQL Database Tables to xPDO Maps and Classes ===
================================================================================
 
SYNOPSIS:
This script generates the XML schema and PHP class files that describe custom
database tables.
 
This script is meant to be executed once only: after the class and schema files
have been created, the purpose of this script has been served.
 
USAGE:
1. Upload this file to the root of your MODx installation
2. Set the configuration details below
3. Navigate to this script in a browser to execute it,
    e.g. http://yoursite.com/thisscript.php
    or, you can do this via the command line, e.g. php this-script.php
 
INPUT:
Please configure the options below.
 
OUTPUT:
Creates XML and PHP files:
    core/components/$package_name/model/$package_name/*.class.php
    core/components/$package_name/model/$package_name/mysql/*.class.php
    core/components/$package_name/model/$package_name/mysql/*.inc.php
    core/components/$package_name/schema/$package_name.mysql.schema.xml
 
SEE ALSO:
http://modxcms.com/forums/index.php?topic=40174.0
http://rtfm.modx.com/display/revolution20/Using+Custom+Database+Tables+in+your+3rd+Party+Components
http://rtfm.modx.com/display/xPDO20/xPDOGenerator.writeSchema
------------------------------------------------------------------------------*/
 
/*------------------------------------------------------------------------------
        CONFIGURATION
------------------------------------------------------------------------------
Be sure to create a valid database user with permissions to the appropriate
databases and tables before you try to run this script, e.g. by running
something like the following:
 
CREATE USER 'your_user'@'localhost' IDENTIFIED BY 'y0urP@$$w0rd';
GRANT ALL ON your_db.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
 
Be sure to test that the login criteria you created actually work before
continuing. If you *can* log in, but you receive errors (e.g. SQLSTATE[42000] [1044] )
when this script runs, then you may need to grant permissions for CREATE TEMPORARY TABLES
------------------------------------------------------------------------------*/
$debug = false;     // if true, will include verbose debugging info, including SQL errors.
$verbose = true;    // if true, will print status info.
 
// The XML schema file *must* be updated each time the database is modified, either
// manually or via this script. By default, the schema is regenerated.
// If you have spent time adding in composite/aggregate relationships to your
// XML schema file (i.e. foreign key relationships), then you may want to set this
// to 'false' in order to preserve your custom modifications.
$regenerate_schema = true;
 
// Class files are not overwritten by default
$regenerate_classes = true;
 
// Your package shortname:
$package_name = '';
 
 
// Database Login Info can be set explicitly:
$database_server    = 'localhost';      // most frequently, your database resides locally
$dbase          = '';       // name of your database
$database_user      = '';       // name of the user
$database_password  = '';   // password for that database user
 
// If your tables use a prefix, this will help identify them and it ensures that
// the class names appear "clean", without the prefix.
$table_prefix = 'axpr_';

// If you specify a table prefix, you probably want this set to 'true'. E.g. if you
// have custom tables alongside the modx_xxx tables, restricting the prefix ensures
// that you only generate classes/maps for the tables identified by the $table_prefix.
$restrict_prefix = true;
 
 
 
 
//------------------------------------------------------------------------------
//  DO NOT TOUCH BELOW THIS LINE
//------------------------------------------------------------------------------
$docroot = dirname(__FILE__);
while (!file_exists($docroot.'/config.core.php')) {
    if ($docroot == '/') {
        die('Failed to locate config.core.php');
    }
    $docroot = dirname($docroot);
}
if (!file_exists($docroot.'/config.core.php')) {
    die('Failed to locate config.core.php');
}
require_once $docroot.'/config.core.php';

if (!defined('MODX_CORE_PATH')) {
    print_msg('<h1>Reverse Engineering Error</h1>
        <p>MODX_CORE_PATH not defined! Did you include the correct config file?</p>');
    exit;
}
 
$xpdo_path = strtr(MODX_CORE_PATH . 'xpdo/xpdo.class.php', '\\', '/');
include_once  $xpdo_path ;
 
// A few definitions of files/folders:
$package_dir = MODX_CORE_PATH . "components/$package_name/";
$model_dir = MODX_CORE_PATH . "components/$package_name/model/";
$class_dir = MODX_CORE_PATH . "components/$package_name/model/$package_name";
$schema_dir = MODX_CORE_PATH . "components/$package_name/model/schema";
$mysql_class_dir = MODX_CORE_PATH . "components/$package_name/model/$package_name/mysql";
$xml_schema_file = MODX_CORE_PATH . "components/$package_name/model/schema/$package_name.mysql.schema.xml";
 
// A few variables used to track execution times.
$mtime= microtime();
$mtime= explode(' ', $mtime);
$mtime= $mtime[1] + $mtime[0];
$tstart= $mtime;
 
// Validations
if ( empty($package_name) ) {
    print_msg('<h1>Reverse Engineering Error</h1>
        <p>The $package_name cannot be empty!  Please adjust the configuration and try again.</p>');
    exit;
}
 
// Create directories if necessary
$dirs = array($package_dir, $schema_dir ,$mysql_class_dir, $class_dir);
 
foreach ($dirs as $d) {
    if ( !file_exists($d) ) {
        if ( !mkdir($d, 0777, true) ) {
            print_msg( sprintf('<h1>Reverse Engineering Error</h1>
                <p>Error creating <code>%s</code></p>
                <p>Create the directory (and its parents) and try again.</p>'
                , $d
            ));
            exit;
        }
    }
    if ( !is_writable($d) ) {
        print_msg( sprintf('<h1>Reverse Engineering Error</h1>
            <p>The <code>%s</code> directory is not writable by PHP.</p>
            <p>Adjust the permissions and try again.</p>'
        , $d));
        exit;
    }
}
 
if ( $verbose ) {
    print_msg( sprintf('<br/><strong>Ok:</strong> The necessary directories exist and have the correct permissions inside of <br/>
        <code>%s</code>', $package_dir));
}
 
// Delete/regenerate map files?
if ( file_exists($xml_schema_file) && !$regenerate_schema && $verbose) {
    print_msg( sprintf('<br/><strong>Ok:</strong> Using existing XML schema file:<br/><code>%s</code>',$xml_schema_file));
}
 
$xpdo = new xPDO("mysql:host=$database_server;dbname=$dbase", $database_user, $database_password, $table_prefix);
 
// Set the package name and root path of that package
$xpdo->setPackage($package_name, $package_dir, $package_dir);
$xpdo->setDebug($debug);
 
$manager = $xpdo->getManager();
$generator = $manager->getGenerator();
 
//Use this to create an XML schema from an existing database
if ($regenerate_schema) {
    $xml = $generator->writeSchema($xml_schema_file, $package_name, 'xPDOObject', $table_prefix, $restrict_prefix);
    if ($verbose)
    {
        print_msg( sprintf('<br/><strong>Ok:</strong> XML schema file generated: <code>%s</code>',$xml_schema_file));
    }
}
 
// Use this to generate classes and maps from your schema
if ($regenerate_classes) {
 
    print_msg('<br/>Attempting to remove/regenerate class files...');
    delete_class_files( $class_dir );
    delete_class_files( $mysql_class_dir );
}
 
// This is harmless in and of itself: files won't be overwritten if they exist.
$generator->parseSchema($xml_schema_file, $model_dir);
 
$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);
 
if ($verbose) {
    print_msg("<br/><br/><strong>Finished!</strong> Execution time: {$totalTime}<br/>");
 
    if ($regenerate_schema)
    {
        print_msg("<br/>If you need to define aggregate/composite relationships in your XML schema file, be sure to regenerate your class files.");
    }
}
 
exit ();
 
 
/*------------------------------------------------------------------------------
INPUT: $dir: a directory containing class files you wish to delete.
------------------------------------------------------------------------------*/
function delete_class_files($dir) {
    global $verbose;
 
    $all_files = scandir($dir);
    foreach ( $all_files as $f ) {
        if ( preg_match('#\.class\.php$#i', $f) || preg_match('#\.map\.inc\.php$#i', $f)) {
            if ( unlink("$dir/$f") ) {
                if ($verbose) {
                    print_msg( sprintf('<br/>Deleted file: <code>%s/%s</code>',$dir,$f) );
                }
            }
            else {
                print_msg( sprintf('<br/>Failed to delete file: <code>%s/%s</code>',$dir,$f) );
            }
        }
    }
}
/*------------------------------------------------------------------------------
Formats/prints messages.  The behavior is different if the script is run
via the command line (cli).
------------------------------------------------------------------------------*/
function print_msg($msg) {
    if ( php_sapi_name() == 'cli' ) {
        $msg = preg_replace('#<br\s*/>#i', "\n", $msg);
        $msg = preg_replace('#<h1>#i', '== ', $msg);
        $msg = preg_replace('#</h1>#i', ' ==', $msg);
        $msg = preg_replace('#<h2>#i', '=== ', $msg);
        $msg = preg_replace('#</h2>#i', ' ===', $msg);
        $msg = strip_tags($msg) . "\n";
    }
    print $msg;
}
 
/* EOF */