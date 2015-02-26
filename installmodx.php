#!/usr/bin/php -q
<?php
/**
 * Install/Upgrade MODX via CLI 
 *
 * (requires PHP 5.3.0 or greater)
 *
 * This script installs MODX Revolution locally or it upgrades an 
 * existing installation to the latest version. There are many command-line 
 * options you can use when executing the script, but if none are provided,
 * you will be prompted for the necessary details.
 *
 * WARNING: It is hoped that this script will be useful, but no guarantee is 
 * made or implied: USE THIS SCRIPT AT YOUR OWN RISK!!!
 *
 * Note: this script may fail if there is poor network connectivity or if the 
 * MODX site is unavailable.
 * 
 *
 * PARAMETERS
 *	--config specifies an XML configuration file to use (path is relative to PWD)
 *	--zip specifies a local MODX zip file  (path is relative to PWD)
 *	--target specifies where to extract the zip file, e.g. public_html/ (i.e. the base_path).
 *  --version specifies the version of MODX to install, e.g. 2.2.5-pl. Defaults to
 *          the latest public release.
 *  --installmode : 'new' for new installs, 'upgrade' for upgrades. (default:new).
 *  --core_path : req'd if you are doing an upgrade.
 *
 * SAMPLE USAGE:
 *
 * Download this script to your server (preferably to a location above your web root).  You
 * can copy and paste, or you can use a utility like wget, e.g.
 *
 *      wget https://raw.github.com/craftsmancoding/modx_utils/master/installmodx.php
 *
 * Then run the script via the command line.  The simplest invocation is to just run the 
 * script without any options:
 *
 * 		php installmodx.php
 *
 * You can supply options when you run the script, e.g. to verbosely do a new install:
 *  
 *      php installmodx.php --installmode=new
 *
 * If you already have a zip file downloaded, you can skip the download:
 *
 * 		php installmodx.php --zip=modx-2.2.8-pl.zip
 *
 * You can avoid all kinds of prompts if you supply the script with a configuration XML file:
 *
 * 		php installmodx.php --config=myconfig.php
 * 
 * To upgrade an existing installation, follow the prompts, or supply the following  options:
 *
 *      php installmodx.php --installmode=upgrade --core_path=public_html/core
 *
 *
 * See http://youtu.be/-FR10DR16CE for an example video of this in action.
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 *
 * LAST UPDATED:
 * June 17, 2013
 *
 * SEE ALSO
 * http://rtfm.modx.com/display/revolution20/Command+Line+Installation
 * http://objectmix.com/php/503559-cli-spinner-processing.html
 * http://patorjk.com/software/taag/
 */
 
//------------------------------------------------------------------------------
//! CONFIG (Devs only)
//------------------------------------------------------------------------------
define('MODX_API_MODE', true);
// Web page that shows the most current version of MODX
define('INFO_PAGE', 'http://modx.com/download/'); 
// append the modx version, e.g. modx-2.2.6.zip
define('DOWNLOAD_PAGE', 'http://modx.com/download/direct/');
define('ESC', 27);
// we need PHP 5.3.0 for the CLI options and GOTO statements (yes, really)
define('PHP_REQ_VER', '5.3.0');
define('THIS_VERSION', '2.1');
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
MODX Installer Utility
----------------------------------------------
This utility is designed to let you quickly install MODX Revolution (http://modx.com/) 
to your server via the command-line. 

----------------------------------------------
PARAMETERS:
----------------------------------------------
--config : path to an XML file, containing site info. See http://bit.ly/pvVcHw
--zip : a MODX zip file, downloaded from ".DOWNLOAD_PAGE."
--target : the base_path of your MODX install, can be relative e.g. public_html/
--version : the version of MODX to install, e.g. 2.2.8-pl. Defaults to latest avail.
--installmode : 'new' for new installs (default), 'upgrade' for upgrades.
--core_path : req'd if you are performing an upgrade.
--help : displays this help page.

----------------------------------------------
USAGE EXAMPLES:
----------------------------------------------
php ".basename(__FILE__)."

    This is the most basic invocation. The user will be prompted for all info.

php ".basename(__FILE__)." --zip=modx-2.2.5-pl

    --zip tells the script to extract an existing local zip file instead of
    downloading a new one. Path is relative.

php ".basename(__FILE__)." --config=myconfig.xml

	The --config option specifies a MODX XML configuration.  This file contains 
	your database login, your MODX username, and other important data required 
	to install MODX.  This file will be copied to setup/config.xml. If you are 
	doing a lot of installs, keep a copy of an XML config file. Path is relative.

php ".basename(__FILE__)." --target=public_html

	The --target option specifies where to deploy MODX. No intermediate
	directories will be created: the contents of the zip file will go to the 
	target. Path is relative.

----------------------------------------------
BUGS and FEATURE SUGGESTIONS
----------------------------------------------
Please direct feedback about this script to https://github.com/craftsmancoding/modx_utils

";
}

/**
 * Strip the front off the dir name to make for cleaner zipfile extraction.
 * Converts something like myzipdir/path/to/file.txt
 * to path/to/file.txt
 *
 * Yes, this is some childish tricks here using string reversal, but we 
 * get the biggest bang for our buck using dirname().
 * @param string $path
 * @return string 
 */
function strip_first_dir($path) {
	$path = strrev($path);
	$path = dirname($path);
	$path = strrev($path);
	return $path;
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
	if (!extension_loaded('curl')) {
		abort("Sorry, this script requires the curl extension for PHP.");
	}
	if (!class_exists('ZipArchive')) {
		abort("Sorry, this script requires the ZipArchive classes for PHP.");
	}
	// timezone
	if (!ini_get('date.timezone')) {
		abort("You must set the date.timezone setting in your php.ini. Please set it to a proper timezone before proceeding.");
	}
}

/** 
 * Eye Candy
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

           ,--.                ,--.          ,--.,--.               
           |  |,--,--,  ,---.,-'  '-. ,--,--.|  ||  | ,---. ,--.--. 
           |  ||      \(  .-''-.  .-'' ,-.  ||  ||  || .-. :|  .--' 
           |  ||  ||  |.-'  `) |  |  \ '-'  ||  ||  |\   --.|  |    
           `--'`--''--'`----'  `--'   `--`--'`--'`--' `----'`--'    

                                                       
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
	$shortopts .= 'c::'; // Optional value
	$shortopts .= 'z::'; // Optional value
	$shortopts .= 't::'; // Optional value
	$shortopts .= 'v::'; // Optional value
	$shortopts .= 'i::'; // Optional value	
	$shortopts .= 'p::'; // Optional value	
	$shortopts .= 'h';   // Optional value
	
	$longopts  = array(
	    'config::',        // Optional value
	    'zip::',           // Optional value
	    'target::',        // Optional value
	    'version::',       // Optional value
	    'installmode::',   // Optional value
	    'core_path::',     // Optional value
	   	'help',            // Optional value
	);
	
	$opts = getopt($shortopts, $longopts);
	
	if (isset($opts['help'])) {
		show_help();
		teardown();
	}
	
	if (isset($opts['config'])) {
        if (!file_exists($opts['config'])) {
		  abort('XML configuration file not found. ' . $opts['config']);
        }
	}
	else {
		$opts['config'] = false;
	}
	if (isset($opts['zip']) && !file_exists($opts['zip'])) {
		abort('Zip file not found. ' . $opts['zip']);
	}
	if (!isset($opts['target'])) {
		$opts['target'] = null;
	}
	if (!isset($opts['version'])) {
	   $opts['version'] = 'latest';
	}
	if (!isset($opts['installmode'])) {
	   $opts['installmode'] = '';
    }
    elseif (!in_array($opts['installmode'], array('new','upgrade'))) {
        show_help();
        abort('Invalid argument for --installmode. It supports "new" or "upgrade"');
    }
    elseif($opts['installmode'] == 'upgrade') {
        if (!isset($opts['core_path'])) {
            abort('--core_path must be set for upgrades: we need it to locate your existing installation.');
        }
        // Careful: realpath may return false
        $opts['core_path'] = realpath($opts['core_path']).DIRECTORY_SEPARATOR;
        if (!file_exists($opts['core_path'] .'config/config.inc.php')) {
            abort('Invalid --core_path. Could not locate '.$opts['core_path'] .'config/config.inc.php');   
        }        
    }

    if (!isset($opts['core_path'])) {
        $opts['core_path'] = '';
    }
	return $opts;
}

/** 
 * Finds the name of the lastest stable version of MODX
 * by scraping the MODX website.  Prints some messaging...
 *
 * @return string
 */
function get_latest_modx_version() {
	print "Finding most recent version of MODX...";
	$contents = file_get_contents(INFO_PAGE);
	preg_match('#'.preg_quote('<h3>MODX Revolution ').'(.*)'. preg_quote('</h3>','/').'#msU',$contents,$m1);
	if (!isset($m1[1])) {
	    abort('Version could not be detected on '. INFO_PAGE);
	}
	print $m1[1] . PHP_EOL;
	return $m1[1];
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
	printf("%c8Downloading $zip_url... (".$cursorArray[ (($i++ > 7) ? ($i = 1) : ($i % 8)) ].")", ESC); 
}

/**
 *
 * When finished, you should have a modx-x.x.x.zip file locally on your system.
 * @param string $modx_zip e.g. modx-2.2.6-pl.zip (format is "modx-" + version + ".zip")
 */
function download_modx($modx_zip) {
	global $zip_url;
	$zip_url = DOWNLOAD_PAGE.$modx_zip;
	$local_file = $modx_zip; // TODO: different location?
	print "Downloading from $zip_url".PHP_EOL;
	printf( "%c[2J", ESC ); //clear screen
	
	$fp = fopen($local_file, 'w');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $zip_url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_NOPROGRESS, false); // req'd to allow callback
	curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress_indicator');
	curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // bigger = fewer callbacks
	if (curl_exec($ch) === false) {
		abort("There was a problem downloading the zip file: " .curl_error($ch));
	}
	else {
		print PHP_EOL;
		print "Zip file downloaded to $local_file".PHP_EOL;
	}
	curl_close($ch);
	fclose($fp);	
}

/**
 * ZipArchive::extractTo did not do what I wanted, and it had errors. Boo.
 * The trick is to shift the "modx-2.2.6-pl" off from the front of the 
 * extraction. Instead of extracting to public_html/modx-2.2.6-pl/ we want
 * to extract straight to public_html/
 * I couldn't find any other examples that did quite what I wanted.
 *
 * See http://stackoverflow.com/questions/5256551/unzip-the-file-using-php-collapses-the-zip-file-into-one-folder
 *
 * @param string $zipfile (relative to this script, e.g. myfile.zip)
 * @param string $target path where we want to install MODX, e.g. public_html/
 * @param boolean $verbose. If true, file names are printed as they are extracted
 */
function extract_zip($zipfile,$target,$verbose=false) {

	$z = zip_open($zipfile) or die("can't open $zipfile: $php_errormsg");
	while ($entry = zip_read($z)) {
		
		$entry_name = zip_entry_name($entry);

		// only proceed if the file is not 0 bytes long
		if (zip_entry_filesize($entry)) {
			// Put this in our own directory
			$entry_name = $target . strip_first_dir($entry_name);
			if ($verbose) {
                print 'inflating: '. $entry_name .PHP_EOL;
			}
			else {
                print '.';
			}
			$dir = dirname($entry_name);
			// make all necessary directories in the file's path
			if (!is_dir($dir)) { 
				@mkdir($dir,0777,true); 
			}
				
			$file = basename($entry_name);
			
			if (zip_entry_open($z,$entry)) {
				if ($fh = fopen($dir.'/'.$file,'w')) {
					// write the entire file
					fwrite($fh,
					zip_entry_read($entry,zip_entry_filesize($entry)))
					or error_log("can't write: $php_errormsg");
					fclose($fh) or error_log("can't close: $php_errormsg");
				} 
				else {
					print "Can't open $dir/$file".PHP_EOL;
				}
				zip_entry_close($entry);
			} 
			else {
				print "Can't open entry $entry_name" . PHP_EOL;
			}
		}
	}
	
	print 'Extraction complete.'.PHP_EOL.PHP_EOL;
}

/**
 * Prompt the user for the deets. Some of this we can detect already.
 *
 * @return array
 */
function get_data($data) {

	print '-------------------------------------------------------------' . PHP_EOL;
	print 'Provide your configuration details below.'.PHP_EOL;
	print 'If you are unsure about a setting, accept the default value.'.PHP_EOL;
	print '(You can review your choices before you install).'.PHP_EOL;
	print '-------------------------------------------------------------' . PHP_EOL.PHP_EOL;
	
	// Add some descriptive labels to any field that needs extra descriptions
	$help = array();
    $help['database_type'] = 'Database Type';
	$help['database_server'] = 'Database Server';
	$help['database'] = 'Database Name';
	$help['database_user'] = 'Database User';
	$help['database_password'] = 'Database Password';
    $help['database_connection_charset'] = 'Database Charset';
	$help['database_collation'] = 'Database Collation';
	$help['table_prefix'] = 'Table Prefix';
		
	$help['cmsadmin'] = 'MODX Admin Username';
	$help['cmsadminemail'] = 'MODX Admin Email';
	$help['cmspassword'] = 'MODX Admin Password';

	$help['core_path'] = 'Core Folder (relative to the base path)';
	$help['base_url'] = 'Base URL (change this only if you are installing to a sub-directory)';
	$help['mgr_url'] = 'Manager URL segment (change to a non-standard location for security)';
	$help['connectors_url'] = 'Connectors URL segment';	
	
	foreach($data as $k => $v) {
		$default_label = ''; // with [brackets]
		if (!empty($v)) {
			$default_label = " [$v]";
		}
		if (isset($help[$k])) {
		  print $help[$k] . $default_label.': ';
		}
		else {
    		print $k . $default_label.': ';
		}
			
		$input = trim(fgets(STDIN));
		if (!empty($input)) {
			$data[$k] = $input;
		}
		else {
			$data[$k] = $v;
		}
	}

	return $data;
}

/**
 * Prints data so the user can review it.
 * @param array $data
 */
function print_review_data($data) {
	printf( "%c[2J", ESC ); //clear screen
	print '-----------------------------------------' . PHP_EOL;
	print 'Review your configuration details.'.PHP_EOL;
	print '-----------------------------------------' . PHP_EOL.PHP_EOL;
	
	foreach ($data as $k => $v) {
		printf( "%' -24s", $k.':');
		print $v . PHP_EOL;
	}
}

/**
 * Get XML configuration file that MODX will recognize.
 * This is streamlined for the most common options.
 *
 * @param array $data
 */
function get_xml($data) {	
	
	// Write XML File
	$xml = '<?xml version="1.0" ?>
<!--
Configuration file for MODX Revolution

Created by the modxinstaller.php script.
https://github.com/craftsmancoding/modx_utils
-->
<modx>
	<database_type>'.htmlspecialchars($data['database_type'],ENT_NOQUOTES).'</database_type>
    <database_server>'.htmlspecialchars($data['database_server'],ENT_NOQUOTES).'</database_server>
    <database>'.htmlspecialchars($data['database'],ENT_NOQUOTES).'</database>
    <database_user>'.htmlspecialchars($data['database_user'],ENT_NOQUOTES).'</database_user>
    <database_password>'.htmlspecialchars($data['database_password'],ENT_NOQUOTES).'</database_password>
    <database_connection_charset>'.htmlspecialchars($data['database_charset'],ENT_NOQUOTES).'</database_connection_charset>
    <database_charset>'.htmlspecialchars($data['database_charset'],ENT_NOQUOTES).'</database_charset>
    <database_collation>'.htmlspecialchars($data['database_collation'],ENT_NOQUOTES).'</database_collation>
    <table_prefix>'.htmlspecialchars($data['table_prefix'],ENT_NOQUOTES).'</table_prefix>
    <https_port>443</https_port>
    <http_host>localhost</http_host>
    <cache_disabled>0</cache_disabled>

    <!-- Set this to 1 if you are using MODX from Git or extracted it from the full MODX package to the server prior
         to installation. -->
    <inplace>0</inplace>
    
    <!-- Set this to 1 if you have manually extracted the core package from the file core/packages/core.transport.zip.
         This will reduce the time it takes for the installation process on systems that do not allow the PHP time_limit
         and Apache script execution time settings to be altered. -->
    <unpacked>0</unpacked>

    <!-- The language to install MODX for. This will set the default manager language to this. Use IANA codes. -->
    <language>en</language>

    <!-- Information for your administrator account -->
    <cmsadmin>'.htmlspecialchars($data['cmsadmin'],ENT_NOQUOTES).'</cmsadmin>
    <cmspassword>'.htmlspecialchars($data['cmspassword'],ENT_NOQUOTES).'</cmspassword>
    <cmsadminemail>'.htmlspecialchars($data['cmsadminemail'],ENT_NOQUOTES).'</cmsadminemail>

    <!-- Paths for your MODX core directory -->
    <core_path>'.htmlspecialchars($data['core_path'],ENT_NOQUOTES).'</core_path>

    <!-- Paths for the default contexts that are installed. -->
    <context_mgr_path>'.htmlspecialchars($data['mgr_path'],ENT_NOQUOTES).'</context_mgr_path>
    <context_mgr_url>'.htmlspecialchars($data['mgr_url'],ENT_NOQUOTES).'</context_mgr_url>
    <context_connectors_path>'.htmlspecialchars($data['connectors_path'],ENT_NOQUOTES).'</context_connectors_path>
    <context_connectors_url>'.htmlspecialchars($data['connectors_url'],ENT_NOQUOTES).'</context_connectors_url>
    <context_web_path>'.htmlspecialchars($data['base_path'],ENT_NOQUOTES).'</context_web_path>
    <context_web_url>'.htmlspecialchars($data['base_url'],ENT_NOQUOTES).'</context_web_url>

    <!-- Whether or not to remove the setup/ directory after installation. -->
    <remove_setup_directory>1</remove_setup_directory>
</modx>';
	
	return $xml;
}

/**
 * From http://www.php.net/manual/en/function.copy.php#91256
 * Copy file or folder from source to destination
 * @param string $source file or folder
 * @param string $dest   file or folder
 * @param array $options (optional) folderPermission,filePermission
 * @return boolean
 */
function recursive_copy($source, $dest, $options=array('folderPermission'=>0755, 'filePermission'=>0755)) {
	$result=false;

	if (is_file($source)) {
		if ($dest[strlen($dest)-1]=='/') {
			if (!file_exists($dest)) {
				cmfcDirectory::makeAll($dest, $options['folderPermission'], true);
			}
			$__dest=$dest.'/'.basename($source);
		} 
		else {
			$__dest=$dest;
		}
		$result=copy($source, $__dest);
		chmod($__dest, $options['filePermission']);

	} 
	elseif (is_dir($source)) {
		if ($dest[strlen($dest)-1]=='/') {
			if ($source[strlen($source)-1]=='/') {
				//Copy only contents
			} 
			else {
				//Change parent itself and its contents
				$dest=$dest.basename($source);
				@mkdir($dest);
				chmod($dest, $options['filePermission']);
			}
		} 
		else {
			if ($source[strlen($source)-1]=='/') {
				//Copy parent directory with new name and all its content
				@mkdir($dest, $options['folderPermission']);
				chmod($dest, $options['filePermission']);
			} 
			else {
				//Copy parent directory with new name and all its content
				@mkdir($dest, $options['folderPermission']);
				chmod($dest, $options['filePermission']);
			}
		}

		$dirHandle=opendir($source);
		while ($file=readdir($dirHandle)) {
			if ($file!='.' && $file!='..') {
				if (!is_dir($source."/".$file)) {
					$__dest=$dest.'/'.$file;
				} 
				else {
					$__dest=$dest.'/'.$file;
				}
				//echo "$source/$file ||| $__dest<br />";
				$result=recursive_copy($source."/".$file, $__dest, $options);
			}
		}
		closedir($dirHandle);

	} 
	else {
		$result=false;
	}
	return $result;
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
 * Delete a non-empty directory (recursivley)
 * http://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Write XML data to a file.
 * @param string $contents
 * @param string $xml_path where we write this
 */
function write_xml($contents,$xml_path) {
	if($fh = @fopen($xml_path, 'w')) {
		fwrite($fh, $contents);
		fclose($fh);
		
		print 'config.xml file written at '.$xml_path.PHP_EOL;
	}
	else {
		print 'There was a problem opening '.$xml_path.' for writing.'.PHP_EOL;
		print 'You can paste the following contents into '.$xml_path.PHP_EOL;
		print 'and then run:  php ./index.php --installmode=new'. PHP_EOL;
		print 'or navigate to your site via a browser and do a normal installation.'.PHP_EOL.PHP_EOL;
		print $contents;
		print PHP_EOL.PHP_EOL;
		teardown();
	}
}

/**
 * Some assembly is required after opening the MODX package.
 * So we set up a few things in MODX for a better experience.
 *
 * @param string $target
 */
function prepare_modx_new($data) {
	$base_path = $data['base_path'];
	$core_path = $data['core_path'];
	// Check that core/cache/ exists and is writeable
	if (!file_exists($core_path.'cache')) {
		@mkdir($core_path.'cache',0777,true); 
	}
    chmod($core_path.'cache', DIR_PERMS);

	
	// Check that core/components/ exists and is writeable
	if (!file_exists($core_path.'components')) {
		@mkdir($core_path.'components',0777,true); 
	}
	chmod($core_path.'components', DIR_PERMS);

	// Check that assets/components/ exists and is writeable
	if (!file_exists($base_path.'assets/components')) {
		@mkdir($base_path.'assets/components',0777,true); 
	}
    chmod($base_path.'assets/components', DIR_PERMS);

	// Check that core/export/ exists and is writable
	if (!file_exists($core_path.'export')) {
		@mkdir($core_path.'export',0777,true); 
	}
    chmod($core_path.'export', DIR_PERMS);
	
	// touch the config file
	if (!file_exists($core_path.'config/config.inc.php')) {
		@mkdir($core_path.'config',0777,true); 
		touch($core_path.'config/config.inc.php');
	}
    chmod($core_path.'config/config.inc.php', DIR_PERMS);
	
	// Lock down the core: activate core/ht.access
	@rename($core_path.'ht.access', $core_path.'.htaccess');

}

/**
 * Logout all users, clear the cache, make sure config file is writable
 *
 */
function prepare_modx_upgrade($data) {
    $core_path = $data['core_path'];
    chmod($core_path.'config/config.inc.php', DIR_PERMS);
    
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
 * For clean breaks
 */
function teardown() {
    global $src, $target;
    print "Cleaning up $src". PHP_EOL;
    rrmdir($src);
    print "Cleaning up $target".'setup'. PHP_EOL;
    rrmdir($target.'setup');
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
// declared here in the main scope so we can use it in the progress indicator.
$zip_url = '';

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
print 'This script installs or updates the MODX Content Management System (http://modx.com/)'.PHP_EOL;
print 'You need a dedicated database with a username/password and your user'.PHP_EOL;
print 'must have the proper write permissions for this script to work properly.'.PHP_EOL.PHP_EOL;
print 'For upgrades, this script must be placed on the same drive as an existing MODX installation.'.PHP_EOL.PHP_EOL;
print 'Are you ready to continue? (y/n) [n] > ';
$yn = strtolower(trim(fgets(STDIN)));
if ($yn!='y') {
	print 'Catch you next time.' .PHP_EOL.PHP_EOL;
	teardown();
}
print PHP_EOL;

// New install or upgrade?
if (!$args['installmode']) {
    print 'Is this a new install? [y] > ';
    $yn = strtolower(trim(fgets(STDIN)));
    if ($yn == 'n') {
        $args['installmode'] = 'upgrade';
    }
    else {
        $args['installmode'] = 'new';
    }
}

// Skip downloading if we've already got a zip file
if (isset($args['zip']) && !empty($args['zip'])) {
	print 'Using existing zip file: '.$args['zip'] . PHP_EOL;
}
else {
    if ($args['version'] == 'latest') {
        $args['version'] = get_latest_modx_version();
    }
	$modx_zip = 'modx-'.$args['version'].'.zip';
	
	// If we already have the file downloaded, can we use the existing zip?
	if (file_exists($modx_zip)) { 
		print $modx_zip .' was detected locally on the filesystem.'.PHP_EOL.PHP_EOL;
		print 'Would you like to use that zip file? (y/n) [y] > ';
		$yn = strtolower(trim(fgets(STDIN)));
		if ($yn == 'n') {
            print 'Downloading zip file...'.PHP_EOL;
			download_modx($modx_zip);
		}
	}
	else {
        print 'Downloading zip file...'.PHP_EOL;
		download_modx($modx_zip);
	}
	// At this point, behavior is as if we had specified the zip file verbosely.
	$args['zip'] = $modx_zip;
}

// New Intalls: Prompt user for target
if ($args['installmode'] == 'new') {
    if (!$args['target']) {
    	$args['target'] = pathinfo($args['zip'],PATHINFO_FILENAME).DIRECTORY_SEPARATOR;
    	print PHP_EOL."Where should this be extracted? [".$args['target']."] > ";
    	$target_path = trim(fgets(STDIN));
    	if (!empty($target_path)) {
    		$args['target'] = $target_path;
    	}
    }
    // Does the target exist?
    if (file_exists($args['target'])) {
        if (!is_dir($args['target'])) {
            abort($args['target'] .' must be a directory!');
        }
    }
    else {
        @mkdir($args['target'],0777,true);
    }
    // make sure we have a trailing slash on the target dir
    // REMEMBER: realpath returns false if the dir doesn't exist
    $target = realpath($args['target']).DIRECTORY_SEPARATOR;	
}
// Upgrades: Prompt user for the core_path
elseif ($args['installmode'] == 'upgrade' && !$args['core_path']) {
    ENTERTARGET:
	print PHP_EOL."Where is the existing MODX core directory? (absolute or relative to this script) > ";
	$core_path = trim(fgets(STDIN));
    if (empty($core_path)) {
        print PHP_EOL."Input required.";
        goto ENTERTARGET;
    }
    if (!file_exists($core_path)) {
        print PHP_EOL."ERROR: directory does not exist.";
        goto ENTERTARGET;    
    }
    if (!is_dir($core_path)) {
        print PHP_EOL."ERROR: Target must be a directory.";
        goto ENTERTARGET; 
    }
    $args['core_path'] = rtrim($core_path,'/').DIRECTORY_SEPARATOR;
    // Verify that it is a MODX install?
    if (!file_exists($args['core_path'] .'config/config.inc.php')) {
        print PHP_EOL.'ERROR: '.$args['core_path'] .'config/config.inc.php does not exist.';
        goto ENTERTARGET; 
    }

}

// Did we actually get the zip file?
if (!filesize($args['zip'])) {
    abort($args['zip'] . ' is an empty file. Did you specify a valid version? Or did the download fail?');
}


// Get ourselves a random dir we can unzip to. This dir will be the src dir for the copy operation
$tmpdir = 'tmp_modx_'.substr(md5('installmodx'.time()),-5);
// Does the staging area src directory exist?
if (file_exists($tmpdir)) {
    abort('Whoops. We wanted to use '.$tmpdir.' as a tmp dir, but it already exists.');
}
else {
    @mkdir($tmpdir,0777,true);
}
$src = realpath($tmpdir);

// We can skip a lot of stuff if the user supplied an XML config...
// otherwise we have to ask them a bunch of stuff.
// Yes, and we even have a GOTO statement.
$xml_path = $target.'setup/config.xml';

$data = array();

// If we are upgrading, we can read everything we need.  Our XML config only needs these items
// inplace, unpacked, language, remove_setup_directory
// We use the same XML body, so we have null out the placeholders
if ($args['installmode'] == 'upgrade') {
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
}
elseif (!$args['config']) {	
    
    $error_flag = false;
    
	// Put anything here that you want to prompt the user about.
	// If you include a value, that value will be used as the default.
	$data['database'] = '';
	$data['database_user'] = '';
	$data['database_password'] = '';

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
	$data['database_type'] = 'mysql';
	$data['database_server'] = 'localhost';
    $data['database_charset'] = 'utf8';
	$data['database_collation'] = 'utf8_general_ci';
	$data['table_prefix'] = 'modx_';

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

//Check destinations are writeable
print PHP_EOL. 'Checking all files are writeable.';
$writefail=false;
$di = new RecursiveDirectoryIterator($src);
foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
	if(filetype($filename)=='dir') continue;
	$oldfile=str_replace($src.'/',$target,$filename);
	if  (file_exists($oldfile) && !is_writeable($oldfile)) {
		if ($writefail==false) print PHP_EOL.'The following files are not writeable';
		print PHP_EOL.$oldfile;
    	$writefail=true;
    }
}
if ($writefail==true) {
	rrmdir($src); // lose the zip dir
	print PHP_EOL.'Make these files writeable before continuing. Terminating...';
	die ();
}


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

rrmdir($src); // lose the zip dir

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
    `php -d error_reporting=0 {$target}setup/index.php --installmode=new --core_path={$data['core_path']}`;
}
elseif ($args['installmode'] == 'upgrade') {
    print 'Updating MODX...'.PHP_EOL.PHP_EOL;
    $argv[1] = '--installmode=upgrade';
    $argv[2] = '--core_path='.$data['core_path'];
    `php -d error_reporting=0 {$target}setup/index.php --installmode=upgrade --core_path={$data['core_path']}`;
}

// We can't just do this, otherwise setup will kill execution and we never get to teardown.
// @include $target.'setup/index.php';


print PHP_EOL;
print 'You may now log into your MODX installation.'.PHP_EOL;
print 'Thanks for using the MODX installer!'.PHP_EOL.PHP_EOL;

// Tear down: TODO pcntl_signal?
teardown();

/*EOF*/