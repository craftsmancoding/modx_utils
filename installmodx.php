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
 * USAGE (command line):
 * php installmodx.php
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
 * Performs checks prior to running the script.
 *
 */
function preflight() {
	// Test PHP version.
	if (version_compare(phpversion(),PHP_REQ_VER,'<')) { 
		printf("Sorry, this script requires PHP version %s or greater to run.".PHP_EOL, PHP_REQ_VER);
		exit;
	}
	if (!extension_loaded('curl')) {
		print "Sorry, this script requires the curl extension for PHP.".PHP_EOL;
		exit;
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
	    print 'FATAL ERROR: Version could not be detected on '. INFO_PAGE .PHP_EOL;
	    exit;
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
	
	$fp = fopen($local_file, 'w');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $zip_url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_NOPROGRESS, false); // req'd to allow callback
	curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress_indicator');
	curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // bigger = fewer callbacks
	if (curl_exec($ch) === false) {
		print "FATAL ERROR: There was a problem downloading the zip file: " .curl_error($ch);
		exit;
	}
	else {
		print "Zip file downloaded to $local_file".PHP_EOL;
	}
	curl_close($ch);
	fclose($fp);	
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
// get the latest MODX version (scrape the info page)
$modx_version = get_latest_modx_version();
$modx_zip = 'modx-'.$modx_version.'.zip';

// If we already have the file downloaded, can we use the existing zip?
if (file_exists($modx_zip)) { 
	print $modx_zip .' detected locally on the filesystem.'.PHP_EOL;
	print 'Would you like to use that zip file? [y/n]';
	$yn = fgets(STDIN);
	if (strtolower(trim($yn)) == 'n') {
		download_modx($modx_version);
	}
}
else {
	download_modx($modx_version);
}


/*
$zip = new ZipArchive;
if ($zip->open('test.zip') === TRUE) {
  $zip->extractTo('/my/destination/dir/');
  $zip->close();
  echo 'ok';
} else {
  echo 'failed';
}
*/

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

