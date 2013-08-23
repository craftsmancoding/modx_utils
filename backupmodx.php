#!/usr/bin/php -q
<?php
/**
 * Backup MODX via CLI 
 *
 * (requires PHP 5.3.0 or greater)
 *
 * This script backsup MODX Revolution locally creating 2 files:
 *
 *  base.tar.gz
 *  db.sql
 *
 * WARNING: It is hoped that this script will be useful, but no guarantee is 
 * made or implied: USE THIS SCRIPT AT YOUR OWN RISK!!!
 *
 * PARAMETERS
 *	--src specifies base of MODX install (assumes core is somewhere in there)
 *  --target path where backups should be stored (optional: default = backups)
 *
 * SAMPLE USAGE:
 *
 * Download this script to your server (preferably to a location above your web root).  You
 * can copy and paste, or you can use a utility like wget, e.g.
 *
 *      wget https://raw.github.com/craftsmancoding/modx_utils/master/backupmodx.php
 *
 * Then run the script via the command line.  The simplest invocation is to just run the 
 * script without any options:
 *
 * 		php backupmodx.php
 *
 * You can supply options when you run the script, e.g. to specify where the base_path is:
 *  
 *      php installmodx.php --base_path=public_html
 *
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 *
 * LAST UPDATED:
 * June 18, 2013
 *
 */
 
//------------------------------------------------------------------------------
//! CONFIG (Devs only)
//------------------------------------------------------------------------------
define('MODX_API_MODE', true);
define('ESC', 27);
// we need PHP 5.3.0 for the CLI options and GOTO statements (yes, really)
define('PHP_REQ_VER', '5.3.0');
define('THIS_VERSION', '1.0');
define('THIS_AUTHOR', 'Everett Griffiths (everett@craftsmancoding.com)');
define('DIR_PERMS', 0777); // for cache, etc.
// see http://www.tuxradar.com/practicalphp/16/1/1
ignore_user_abort(true);
set_time_limit(0);

//------------------------------------------------------------------------------
//! Functions
//------------------------------------------------------------------------------
/**
 * Our quitting function...
 */
function abort($msg) {
	print PHP_EOL.'FATAL ERROR! '.$msg . PHP_EOL;
	print 'Aborting.'. PHP_EOL.PHP_EOL;
	teardown();
}

/**
 * How to function
 */
function show_help() {

	print "
----------------------------------------------
MODX Backup Utility
----------------------------------------------
This utility is designed to let you quickly backup MODX Revolution (http://modx.com/) 
via the command-line. 

----------------------------------------------
PARAMETERS:
----------------------------------------------
--src : path to your MODX root, the source
--target : path where you want to store your backups
--help : displays this help page.

----------------------------------------------
USAGE EXAMPLES:
----------------------------------------------
php ".basename(__FILE__)."

    This is the most basic invocation. The user will be prompted for all info.


php ".basename(__FILE__)." --src=public_html

	The --src option specifies the root of a MODX site (relative to this script
    or absolute)
	
php ".basename(__FILE__)." --src=public_html --target=backups

	The --target option specifies where to store the backups.

----------------------------------------------
BUGS and FEATURE SUGGESTIONS
----------------------------------------------
Please direct feedback about this script to https://github.com/craftsmancoding/modx_utils

";
}

/**
 * Performs checks prior to running the script.
 *
 */
function preflight() {
	error_reporting(E_ALL);
	// Test PHP version.
	if (version_compare(phpversion(),PHP_REQ_VER,'<')) { 
		abort(sprintf("Sorry, this script requires PHP version %s or greater to run.", PHP_REQ_VER));
	}
	if (!extension_loaded('zip')) {
		abort("Sorry, this script requires the extension for PHP.");
	}
	// timezone
	if (!ini_get('date.timezone')) {
		abort("You must set the date.timezone setting in your php.ini. Please set it to a proper timezone before proceeding.");
	}
}

/** 
 * Eye Candy. See http://patorjk.com/
 *
 */
function print_banner() {
	printf( "%c[2J", ESC ); //clear screen
	print "
 .----------------.  .----------------.  .----------------.  .----------------. 
| .--------------. || .--------------. || .--------------. || .--------------. |
| | ____    ____ | || |     ____     | || |  ________    | || |  ____  ____  | |
| ||_   \  /   _|| || |   .'    `.   | || | |_   ___ `.  | || | |_  _||_  _| | |
| |  |   \/   |  | || |  /  .--.  \  | || |   | |   `. \ | || |   \ \  / /   | |
| |  | |\  /| |  | || |  | |    | |  | || |   | |    | | | || |    > `' <    | |
| | _| |_\/_| |_ | || |  \  `--'  /  | || |  _| |___.' / | || |  _/ /'`\ \_  | |
| ||_____||_____|| || |   `.____.'   | || | |________.'  | || | |____||____| | |
| |              | || |              | || |              | || |              | |
| '--------------' || '--------------' || '--------------' || '--------------' |
 '----------------'  '----------------'  '----------------'  '----------------

                   ___           ___           ___           ___           ___   
    _____         /  /\         /  /\         /__/|         /__/\         /  /\  
   /  /::\       /  /::\       /  /:/        |  |:|         \  \:\       /  /::\ 
  /  /:/\:\     /  /:/\:\     /  /:/         |  |:|          \  \:\     /  /:/\:\
 /  /:/~/::\   /  /:/~/::\   /  /:/  ___   __|  |:|      ___  \  \:\   /  /:/~/:/
/__/:/ /:/\:| /__/:/ /:/\:\ /__/:/  /  /\ /__/\_|:|____ /__/\  \__\:\ /__/:/ /:/ 
\  \:\/:/~/:/ \  \:\/:/__\/ \  \:\ /  /:/ \  \:\/:::::/ \  \:\ /  /:/ \  \:\/:/  
 \  \::/ /:/   \  \::/       \  \:\  /:/   \  \::/~~~~   \  \:\  /:/   \  \::/   
  \  \:\/:/     \  \:\        \  \:\/:/     \  \:\        \  \:\/:/     \  \:\   
   \  \::/       \  \:\        \  \::/       \  \:\        \  \::/       \  \:\  
    \__\/         \__\/         \__\/         \__\/         \__\/         \__\/  
                                                       
";
	print 'Version '.THIS_VERSION.str_repeat(' ', 15).'by '.THIS_AUTHOR.PHP_EOL;
	print str_repeat(PHP_EOL,2);
}

/**
 * Get and vet command line arguments
 * @return array
 */
function get_args() {
	$shortopts  = '';
	$shortopts .= 's::'; // Optional value
	$shortopts .= 't::'; // Optional value
	$shortopts .= 'h';   // Optional value
	
	$longopts  = array(
	    'src::',     // Optional value
	    'target::',        // Optional value
	   	'help',            // Optional value
	);
	
	$opts = getopt($shortopts, $longopts);
	
	if (isset($opts['help'])) {
		show_help();
		teardown();
	}

	if (!isset($opts['src'])) {
	   $opts['src'] = '';
	}
	else {
        if (file_exists($opts['src']) && is_dir($opts['src'])) {
            // TODO: dir is a modx dir?
            $opts['src'] = rel_to_abspath($opts['src']);
        }
	}
	
	if (!isset($opts['target'])) {
	   $opts['target'] = '';
	}
	else {
	   if (file_exists($opts['target'])) {
	       if (!is_dir($opts['target'])) {
	           print "--target must be a directory!".PHP_EOL;
	           $opts['target'] = '';
	       }
	       else {
    	       $opts['target'] = rel_to_abspath(dirname(__FILE__),$opts['target']);
	       }
	   }
	   else {
	       @mkdir($opts['target'],0777,true); 
	       print "Created directory ".$opts['target'].PHP_EOL;
	       $opts['target'] = rel_to_abspath(dirname(__FILE__),$opts['target']);
	   }
	}
	return $opts;
}


/**
 * A simple cli spinner... doesn't show progress, but it lets the user know 
 * something is happening.
 */
function progress_indicator($ch,$str) {
	global $cursorArray;
	global $i;
	global $zip_url;
	//restore cursor position and print
	printf("%c8Zipping up... (".$cursorArray[ (($i++ > 7) ? ($i = 1) : ($i % 8)) ].")", ESC); 
}


/**
 Zip('/folder/to/compress/', './compressed.zip');
 *
 * See http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
 *
 * @param string $source directory with trailing slash
 * @param string $target path where we want to install MODX, e.g. public_html/
 * @param boolean $verbose. If true, file names are printed as they are extracted
 */
function zip_src($source, $destination){
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}



/**
 * Logout all users and clears the cache
 *
 */
function prepare_modx_backup($data) {
    // This might brick if the install isn't working.
    require_once($data['base_path'].'index.php');
    $modx= new modX();
    $modx->initialize('mgr');
    // See http://tracker.modx.com/issues/9916
    $sessionTable = $modx->getTableName('modSession');
    $modx->query("TRUNCATE TABLE {$sessionTable}");
    @$modx->cacheManager->refresh();
}

/**
 * Convert a path $stub relative to a $base path into an absolute path.
 * @string $base path w trailing slash
 * @string $rel relative path stub
 */
function rel_to_abspath($base, $rel) {
    $path = '';
    // if /
    if (substr($rel,0,1) == '/') {
        $path = $rel;
    }
    // if ..
    elseif (substr($rel,0,2) == '..') {
        $path = $base . $rel;
    }
    else {
        $path = $base.basename($rel);
    }
    // Add trailing slash
    return rtrim($path, '/').DIRECTORY_SEPARATOR;
}

/**
 * For clean breaks
 */
function teardown() {
    global $src, $target, $sessiondir;
    print "Cleaning up $src". PHP_EOL;
    rrmdir($src);
    print "Cleaning up $target".'setup'. PHP_EOL;
    rrmdir($target.'setup');
    print "Cleaning up $sessiondir";
    rrmdir($sessiondir);
    exit;
}

//------------------------------------------------------------------------------
//! Vars
//------------------------------------------------------------------------------
$src = '';
$target = '';

// Each spot in the array is a "frame" in our spinner animation
$cursorArray = array('/','-','\\','|','/','-','\\','|'); 
$i = 0; // for spinner iterations

//------------------------------------------------------------------------------
//! MAIN
//------------------------------------------------------------------------------
// check php version, is cli?, can we write to the local dir?, etc...
preflight();

// Read and validate any command-line arguments
$args = get_args();

// TODO: Are we fast-tracked?  Skip the eye-candy and jump somewhere...

// Some eye-candy...
print_banner();

// Last chance to bail...
print 'This script backs up a MODX site from the command line.'.PHP_EOL;
print 'Are you ready to continue? (y/n) [n] > ';
$yn = strtolower(trim(fgets(STDIN)));
if ($yn!='y') {
	print 'Catch you next time.' .PHP_EOL.PHP_EOL;
	teardown();
}
print PHP_EOL;

// Source files
if (!$args['src']) {
    print 'Where is the base path of the MODX site? > ';
    $src = trim(fgets(STDIN));
    if (file_exists($src) && is_dir($src)) {
        $args['src'] = rel_to_abspath(dirname(__FILE__),$src);
    }
    else {
        abort('Invalid source directory '.$src);
    }
}

// Target dir
if (!$args['target']) {
    print 'Where would you like to store your backups? > ';
    $target = trim(fgets(STDIN));
    if (file_exists($target) && is_dir($target)) {
        $args['target'] = rel_to_abspath(dirname(__FILE__),$target);
    }
    else {
       @mkdir($opts['target'],0777,true); 
       print "Created directory ".$target.PHP_EOL;
       $args['target'] = rel_to_abspath(dirname(__FILE__),$target);
    }
}


// If we are upgrading, we can read everything we need.  Our XML config only needs these items
// inplace, unpacked, language, remove_setup_directory
// We use the same XML body, so we have null out the placeholders

    include $args['core_path'] .'config/config.inc.php';
    if (!isset($database_type) || !isset($database_server) || !isset($dbase)
        || !isset($database_user) || !isset($database_password) || !isset($database_connection_charset)
        || !isset($table_prefix)) {
        print 'FATAL ERROR: '.$args['core_path'] .'config/config.inc.php is not a valid MODX config file.';
        teardown();
    }
	$data['database_type'] = $database_type;
	$data['database_server'] = $database_server;
	$data['database'] = $dbase;
	$data['database_user'] = $database_user;
	$data['database_password'] = $database_password;
    $data['database_charset'] = $database_connection_charset;
	$data['database_collation'] = ''; // ??
	$data['table_prefix'] = $table_prefix;
	$data['cmsadmin'] = '';
	$data['cmsadminemail'] = '';
	$data['cmspassword'] = '';
	$data['core_path'] = MODX_CORE_PATH; // get the official path
    $data['base_url'] = MODX_BASE_URL;
	$data['mgr_url'] = MODX_MANAGER_URL;
	$data['connectors_url'] = MODX_CONNECTORS_URL;    
	$data['base_path'] = MODX_BASE_PATH;
	$data['mgr_path'] = MODX_MANAGER_PATH;
	$data['connectors_path'] = MODX_CONNECTORS_PATH;
	// A couple overrides
    $target = $data['base_path'];
    $xml_path = $target.'setup/config.xml';
    $xml = get_xml($data);

    
	// Put anything here that you want to prompt the user about.
	// If you include a value, that value will be used as the default.
	$data['database_type'] = 'mysql';
	$data['database_server'] = 'localhost';
	$data['database'] = '';
	$data['database_user'] = '';
	$data['database_password'] = '';
    $data['database_charset'] = 'utf8';
	$data['database_collation'] = 'utf8_general_ci';
	$data['table_prefix'] = 'modx_';

	$data['core_path'] = 'core';

    $data['base_url'] = '/';
	$data['mgr_url'] = 'manager';
	$data['connectors_url'] = 'connectors';
	
	
	$data['cmsadmin'] = '';
	$data['cmsadminemail'] = '';
	$data['cmspassword'] = '';


	ENTERNEWDATA:
	$data = get_data($data);
	print_review_data($data);
	
	print PHP_EOL. "Is this correct? (y/n) [n] >";
	$yn = strtolower(trim(fgets(STDIN)));
	if ($yn != 'y') {
		goto ENTERNEWDATA; // 1980 called and wants their code back.
	}
    // Anything that needs to appear in the XML file but that you don't want
    // to prompt the user about should appear down here.
    // Some Sanitization/validation
//    $data['core_path'] = $target.basename($data['core_path']).DIRECTORY_SEPARATOR;
    $data['core_path'] = rel_to_abspath($target, $data['core_path']);
    $base_url = basename($data['base_url']);    
    if (empty($base_url)) {
        $data['base_url'] = '/';
    }
    else {
        $data['base_url'] = '/'.basename($data['base_url']).'/';
    }
	$data['mgr_url'] = $data['base_url'].basename($data['mgr_url']).'/';
	$data['connectors_url'] = $data['base_url'].basename($data['connectors_url']).'/';
	
    
    // --target = --base_path
	$data['base_path'] = $target;
	$data['mgr_path'] = $target.basename($data['mgr_url']).'/';
	$data['connectors_path'] = $target.basename($data['connectors_url']).'/';

    // Security/validation checks
    if (strtolower($data['cmsadmin']) == 'admin') {
        print '"admin" is not allowed as a MODX username because it is too insecure.';
        $error_flag = true;
    }
    if (in_array('setup', array($data['core_path'],$data['base_url'],$data['mgr_url']))) {
        print '"setup" is not allowed as a path or URL option because it is reserved for the installation process.';        
        $error_flag = true;
    }
    if (!filter_var($data['cmsadminemail'], FILTER_VALIDATE_EMAIL)) {
        print 'Invalid email address';
        $error_flag = true;    
    }
    // $data['database_type']
    // $data['database_server']
    // $data['database_user']
    // No duplicates? e.g manager != connectors
    
    if ($error_flag) {
        goto ENTERNEWDATA; 
    }
    
	$xml = get_xml($data);
}
else {
	// Get XML from config file
	$xml = file_get_contents($args['config']);
	// Fill $data array from XML (we need this data in order to do the unzipping correctly)
	$xmldata = simplexml_load_file($args['config']);
	$data['database_type'] = $xmldata->database_type;
	$data['database_server'] = $xmldata->database_server;
	$data['database'] = $xmldata->database;
	$data['database_user'] = $xmldata->database_user;
	$data['database_password'] = $xmldata->database_password;
    $data['database_charset'] = $xmldata->database_connection_charset;
	$data['database_collation'] = $xmldata->database_collation;
	$data['table_prefix'] = $xmldata->table_prefix;
	$data['cmsadmin'] = $xmldata->cmsadmin;
	$data['cmsadminemail'] = $xmldata->cmsadminemail;
	$data['cmspassword'] = $xmldata->cmspassword;
	$data['core_path'] = $xmldata->core_path;
    $data['base_url'] = $xmldata->context_web_url;
	$data['mgr_url'] = $xmldata->context_mgr_url;
	$data['connectors_url'] = $xmldata->context_connectors_url;    
	$data['base_path'] = $xmldata->context_web_path;
	$data['mgr_path'] = $xmldata->context_mgr_path;
	$data['connectors_path'] = $xmldata->context_connectors_path;

}

print PHP_EOL. 'Extracting zip file.';
// Extract the zip to a our temporary src dir
// extract_zip needs the target to have a trailing slash!
extract_zip($args['zip'],$src.DIRECTORY_SEPARATOR,false);
// Move into position 
// (both src and dest. target dirs must NOT contain trailing slash)
recursive_copy($src.'/connectors', $target.basename($data['connectors_path']));
recursive_copy($src.'/core', rtrim($data['core_path'],DIRECTORY_SEPARATOR)); // <-- special since it doesn't have to be in docroot
recursive_copy($src.'/manager', $target.basename($data['mgr_path']));
recursive_copy($src.'/setup', $target.'setup');
recursive_copy($src.'/index.php', $target.'index.php');
recursive_copy($src.'/config.core.php', $target.'config.core.php');
recursive_copy($src.'/ht.access', $target.'ht.access');
// cleanup


// Write the data to the XML file so MODX can read it
write_xml($xml, $xml_path);
if (!$args['config'] && $args['installmode'] != 'upgrade') {
    write_xml($xml, 'config.xml'); // backup for later
}

// TODO: Test Database Connection?
// if upgrade, do some magic
if ($args['installmode'] == 'upgrade') {
    prepare_modx_upgrade($data);
}
else {
    // Check that core/cache exists and is writeable, etc. etc.
    prepare_modx_new($data);
}

//------------------------------------------------------------------------------
// ! Run Setup
//------------------------------------------------------------------------------
// Via command line, we'd do this:
// php setup/index.php --installmode=new --config=/path/to/config.xml
// (MODX will automatically look for the config file inside setup/config.xml)
// but here, we fake it.
unset($argv);
if ($args['installmode'] == 'new') {
    print 'Installing MODX...'.PHP_EOL.PHP_EOL;
    $argv[1] = '--installmode=new';
    $argv[2] = '--core_path='.$data['core_path'];
}
elseif ($args['installmode'] == 'upgrade') {
    print 'Updating MODX...'.PHP_EOL.PHP_EOL;
    $argv[1] = '--installmode=upgrade';
    $argv[2] = '--core_path='.$data['core_path'];
}
@include $target.'setup/index.php';

print PHP_EOL;
print 'You may now log into your MODX installation.'.PHP_EOL;
print 'Thanks for using the MODX installer!'.PHP_EOL.PHP_EOL;

// Tear down: TODO pcntl_signal?
teardown();

/*EOF*/