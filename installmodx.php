#!/usr/bin/php -q
<?php
/**
 * Install MODX via CLI
 *
 * This script downloads latest version of MODX and installs it to the directory you 
 * specify.  The script prompts you for your login details.
 *
 * WARNING: this script may fail if there is poor network connectivity or if the 
 * MODX site is unavailable.
 * 
 * PARAMETERS
 *	--config specifies an XML configuration file to use (rather than prompting the user)
 *	--zip specifies a local MODX zip file  (rather than downloading it fresh)
 *	--target specifies a new path to extract the zip file to (e.g. /home/user/public_html/) 
 *			Default is based on the name of the MODX version and the current working dir.
 *
 * USAGE:
 * 		php installmodx.php
 * 		php installmodx.php --config=myconfig.php
 * 		php installmodx.php --zip=modx-2.2.5-pl.zip
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 *
 * LAST UPDATED:
 * March 12, 2013
 *
 * SEE ALSO
 * http://rtfm.modx.com/display/revolution20/Command+Line+Installation
 * http://objectmix.com/php/503559-cli-spinner-processing.html
 * http://patorjk.com/software/taag/
 */
 
//------------------------------------------------------------------------------
//! CONFIG (Devs only)
//------------------------------------------------------------------------------
// shows the most current version
define('INFO_PAGE', 'http://modx.com/download/'); 
// append the modx version, e.g. modx-2.2.6.zip
define('DOWNLOAD_PAGE', 'http://modx.com/download/direct/');
define('ESC', 27);
// version of PHP this script needs to run.
define('PHP_REQ_VER', '5.3.0');
define('THIS_VERSION', '1.0');
define('THIS_AUTHOR', 'Everett Griffiths (everett@craftsmancoding.com)');

//------------------------------------------------------------------------------
//! Functions
//------------------------------------------------------------------------------
/**
 * Our quitting function...
 */
function abort($msg) {
	print PHP_EOL.'FATAL ERROR! '.$msg . PHP_EOL;
	print 'Aborting.'. PHP_EOL.PHP_EOL;
	exit;
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
}

/** 
 * Eye Candy
 *
 */
function print_banner() {
	printf( "%c[2J", ESC ); //clear screen
	print " .----------------.  .----------------.  .----------------.  .----------------. 
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
	print 'Version '.THIS_VERSION . str_repeat(' ', 15).'by '. THIS_AUTHOR . PHP_EOL;
	print str_repeat(PHP_EOL,5);
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
	
	$longopts  = array(
	    'config::',    // Optional value
	    'zip::',    // Optional value
	    'target::',    // Optional value
	);
	$opts = getopt($shortopts, $longopts);
	
	if (isset($opts['config']) && !file_exists($opts['config'])) {
		abort('Configuration file not found. ' . $opts['config']);
	}
	else {
		$opts['config'] = false;
	}
	if (isset($opts['zip']) && !file_exists($opts['zip'])) {
		abort('Zip file not found. ' . $opts['zip']);
	}
	if (isset($opts['target'])) {
		if (file_exists($opts['target'])) {
			abort('The target directory cannot already exist: '.$opts['target']);
		}
	}
	else {
		$opts['target'] = null;
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
	//restore cursor position and print
	printf("%c8Downloading... (".$cursorArray[ (($i++ > 7) ? ($i = 1) : ($i % 8)) ].")", ESC); 
}

/**
 *
 * When finished, you should have a modx-x.x.x.zip file locally on your system.
 * @param string $modx_version e.g. modx-2.2.6-pl.zip
 */
function download_modx($modx_zip) {
	$zip_url = DOWNLOAD_PAGE.$modx_zip;
	$local_file = $modx_zip; // TODO: different location?
	print "Downloading $zip_url".PHP_EOL;
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
 * Extract the zip file.
 *
 * @param string $zipfile (relative to this script, e.g. myfile.zip)
 * @param string $extractto path where we want to extrat to
 */
/*
function extract_zip($zipfile,$extractto) {
	print "Extracting to $extractto". PHP_EOL;
	$zip = new ZipArchive;
	if ($zip->open($zipfile) === true) {

		//$zip->extractTo($extractto); // This did not work -- if there is a single error, it borks.
		// The "." file generates a warning (wtf?)
		// So we go through item by item.
		for($i = 0; $i < $zip->numFiles; $i++) {
			
//			// This works, but you always end up having a parent $extractto dir
//	        if(!@$zip->extractTo($extractto, array($zip->getNameIndex($i)))) {
//	        	print 'Could not extract file '.$zip->getNameIndex($i).PHP_EOL;
//	        }
//	        else {
//	        	print $zip->getNameIndex($i).PHP_EOL;
//	        }
//
	        $filename = $zip->getNameIndex($i);

			print $filename . PHP_EOL;			
//			copy("zip://".$zipfile."#".$filename, $extractto.'x.txt');
        	$dir = pathinfo($filename,PATHINFO_DIRNAME);
        	if (!file_exists($dir)) {
        		print 'Creating dir '.$dir .PHP_EOL;
        		@mkdir($dir,0777,true);
        	}
//        	print "zip://".$zipfile."#".$filename.', '. $extractto.$filename . PHP_EOL;
        	copy("zip://".$zipfile."#".$filename, $extractto.$filename);
	                    
	    }


		$zip->close();
		print "Extracted zip file $zipfile to $extractto".PHP_EOL;
	} 
	else {
		abort('Could not open zip file' . $zipfile);
	}
}
*/
/**
 * The trick is to shift the "modx-2.2.6-pl" off from the front of the 
 * extraction. Instead of extracting to public_html/modx-2.2.6-pl/ we want
 * to extract straight to public_html/
 *
 * See http://stackoverflow.com/questions/5256551/unzip-the-file-using-php-collapses-the-zip-file-into-one-folder
 */
function extract_zip($zipfile,$extractto) {
	
	$extractto = basename($extractto).DIRECTORY_SEPARATOR; // make sure we have a trailing slash.
	
	$z = zip_open($zipfile) or die("can't open $zipfile: $php_errormsg");
	while ($entry = zip_read($z)) {
		
		$entry_name = zip_entry_name($entry);

		// only proceed if the file is not 0 bytes long
		if (zip_entry_filesize($entry)) {
			// Put this in our own directory
			$entry_name = $extractto . strip_first_dir($entry_name);
			print 'inflating: '. $entry_name .PHP_EOL;
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
					error_log("can't open $dir/$file: $php_errormsg");
				}
				zip_entry_close($entry);
			} 
			else {
				error_log("can't open entry $entry_name: $php_errormsg");
			}
		}
	}
}

/**
 * Check to ensure the directory we think contains MODX actually
 * does appear to contain MODX
 * @param string $dir directory containing MODX.
 */
function verify_modx_dir($dir) {

}

//------------------------------------------------------------------------------
//! Vars
//------------------------------------------------------------------------------
// Each spot in the array is a "frame" in our spinner animation
$cursorArray = array('/','-','\\','|','/','-','\\','|'); 
$i = 0; // for spinner iterations

//------------------------------------------------------------------------------
//! MAIN
//------------------------------------------------------------------------------
// preflight: php version, is cli?, can we write to the local dir?, etc...
preflight();
// Some eye-candy...
print_banner();

// Read and validate any command-line arguments
$args = get_args();

// Skip downloading if we've already got a zip file
if ($args['zip']) {
	print 'Using existing zip file: '.$args['zip'] . PHP_EOL;
}
else {
	// get the latest MODX version (scrape the info page)
	$modx_version = get_latest_modx_version();
	$modx_zip = 'modx-'.$modx_version.'.zip';
	
	// If we already have the file downloaded, can we use the existing zip?
	if (file_exists($modx_zip)) { 
		print $modx_zip .' was detected locally on the filesystem.'.PHP_EOL;
		print 'Would you like to use that zip file? [y/n] > ';
		$yn = fgets(STDIN);
		if (strtolower(trim($yn)) == 'n') {
			download_modx($modx_zip);
		}
	}
	else {
		download_modx($modx_zip);
	}
	// At this point, behavior is as if we had specified the zip file verbosely.
	$args['zip'] = $modx_zip;
}

// Prompt the user for target
if (!$args['target']) {
//	$extractto = getcwd().DIRECTORY_SEPARATOR.pathinfo($args['zip'],PATHINFO_FILENAME)
//		.DIRECTORY_SEPARATOR;
	$extractto = pathinfo($args['zip'],PATHINFO_FILENAME).DIRECTORY_SEPARATOR;
//	$extractto = getcwd().DIRECTORY_SEPARATOR;
	print "Where should this be unzipped to? ($extractto) > ";
	$extractto_path = fgets(STDIN);
	$extractto_path = trim($extractto_path);
	if (!empty($extractto_path)) {
		$extractto = $extractto_path;
	}
}
$zip = getcwd() . DIRECTORY_SEPARATOR.$args['zip'];
extract_zip($args['zip'],$extractto);

// Test Database Connection

// Write XML File
$xml = '<modx>
	<database_type>mysql</database_type>
    <database_server>localhost</database_server>
    <database>modx_modx</database>
    <database_user>db_username</database_user>
    <database_password>db_password</database_password>
    <database_connection_charset>utf8</database_connection_charset>
    <database_charset>utf8</database_charset>
    <database_collation>utf8_general_ci</database_collation>
    <table_prefix>modx_</table_prefix>
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
    <cmsadmin>username</cmsadmin>
    <cmspassword>password</cmspassword>
    <cmsadminemail>email@address.com</cmsadminemail>

    <!-- Paths for your MODX core directory -->
    <core_path>/www/modx/core/</core_path>

    <!-- Paths for the default contexts that are installed. -->
    <context_mgr_path>/www/modx/manager/</context_mgr_path>
    <context_mgr_url>/modx/manager/</context_mgr_url>
    <context_connectors_path>/www/modx/connectors/</context_connectors_path>
    <context_connectors_url>/modx/connectors/</context_connectors_url>
    <context_web_path>/www/modx/</context_web_path>
    <context_web_url>/modx/</context_web_url>

    <!-- Whether or not to remove the setup/ directory after installation. -->
    <remove_setup_directory>1</remove_setup_directory>
</modx>';

// php ./index.php --installmode=new --config=/path/to/config.xml

/*EOF*/

