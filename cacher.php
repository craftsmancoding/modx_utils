<?php
/**
 * Cacher
 * Iterate through MODX page URLs and request each one using file_get_contents. 
 * Requesting the page causes MODX to cache components.
 *
 *
 * INSTALLATION:
 * Copy this file in its entirety somewhere into the webroot of your MODX Revo site.
 * Alternatively, use wget to download the file directly from github:
 *
 *  wget https://raw.githubusercontent.com/craftsmancoding/modx_utils/master/cacher.php
 *
 * USAGE:
 *
 *      php cacher.php --sleep=10
 *      php cacher.php '--where={"parent:IN":[6,7,8]}'
 *
 * See --help for more.
 *
 * AUTHOR:
 * Everett Griffiths
 */
// This can only be run via cli
if (php_sapi_name() !== 'cli') die('CLI access only.');


/**
 * Colorize text for cleaner CLI UX. 
 * TODO: Windows compatible?
 *
 * Adapted from 
 * http://softkube.com/blog/generating-command-line-colors-with-php/
 * http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 * 
 * @param string $text
 * @param string $status
 * @return string message formatted for CLI
 */
function message($text, $status) {
    $out = '';
    switch($status) {
        case 'SUCCESS':
            $out = '[42m SUCCESS: '.chr(27).'[0;32m '; //Green background
            break;
        case 'ERROR':
            $out = '[41m ERROR: '. chr(27).'[0;31m '; //Red
            break;
        case 'WARNING':
            $out = '[43m WARNING: '; //Yellow background
            break;
        case 'INFO':
            $out = '[46m NOTE: '. chr(27).'[0;34m '; //Blue
            break;
        case 'HEADER':
            $out = '[46m '; //Blue            
            break;
        case 'HELP':
            $out = '[42m HELP: '. chr(27).'[0;32m '; //Green
            break;
        default:
            throw new Exception('Invalid status: ' . $status);
    }
    return "\n".chr(27) . $out . $text .' '. chr(27) . '[0m'."\n\n";
}

/**
 * Parse command line arguments and set defaults.
 *
 * @param array $args
 * @return array
 */
function parse_args($args) {
    $defaults = array(
        'sleep' => 0,  // Number of additional seconds to sleep between requests
        'where' => '{"published":"1"}',
        'limit' => 0, // for testing
        'debug' => false,
        'help' => false,
    );
    foreach($args as $a) {
        if (substr($a,0,2) == '--') {
            if ($equals_sign = strpos($a,'=',2)) {
                $key = substr($a, 2, $equals_sign-2);
                $val = substr($a, $equals_sign+1);
                $defaults[$key] = $val;
            }
            else {
                $flag = substr($a, 2);
                $defaults[$flag] = true;
            }
        }
    }   
    
    $defaults['count'] = (int) $defaults['count']; // enforce integer
    return $defaults;
}

/**
 * Custom sleep function: pause the script between iterations
 * This was built so we can handle fractional arguments to --sleep
 * @param number $seconds
 */
function wait($seconds) { 
    $seconds = abs($seconds); 
    if ($seconds < 1) { 
       usleep($seconds*1000000); 
    }
    else {
       sleep($seconds); 
    }
}

/**
 * Request the given url
 * return time elapsed or false on error.
 */
function request_url($url) {
    $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1664.3 Safari/537.36';
    // initialize curl with given url
    $ch = curl_init($url);
    // make sure we get the header
    curl_setopt($ch, CURLOPT_HEADER, 1);
    // make it a http HEAD request
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    // add useragent
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    //Tell curl to write the response to a variable
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // The maximum number of seconds to allow cURL functions to execute.
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,60);
    // Tell curl to stop when it encounters an error
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
   
    $execute = curl_exec($ch);
   
    // Check if any error occured
    $total_time = false;
    if(!curl_errno($ch)) {
        $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        //echo 'Took ' . $total_time . ' seconds to send a request to ' . $url;
        clearstatcache();
    }
    curl_close($ch); 
    return $total_time; 
}
/**
 * display help message
 */
function show_help() {

    print message('Cacher','HELP');
    print "Command line utility iterating through page URLs in MODX Revolution in order to trigger caching. 
This loads one page at a time and prints the load times for your review.    
    
".message('PARAMETERS:','HEADER')."
 --sleep    Additional number of seconds to wait between page requests so as not to overwhelm the server. (default:0)
 --where    JSON argument to be passed to query made on the modResource collection. (optional)
 --debug    Will display the generated MySQL query. Useful if you are using --where. (default:false)
 --help     Displays this help page.
".message('USAGE EXAMPLES:','HEADER').
"Load up all children in folders 6,7, and 8:
    php ".basename(__FILE__)." '--where={\"parent:IN\":[6,7,8]}'

";
}
//------------------------------------------------------------------------------
//! MAIN BLOCK
//------------------------------------------------------------------------------
// Find MODX: as long this script is inside a MODX webroot, it will run.
function find_modx() {
    $dir = '';
    if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
        $dir = dirname(__FILE__);
        while(true) {
            if (file_exists($dir.'/config.core.php')) {
                include $dir.'/config.core.php';
                break;
            }
            $dir = dirname($dir);
            if ($dir == '/') {
                die("Could not find a valid MODX config.core.php file. Make sure this script is in a MODX webroot");
            }
        }
    }
    
    if (!defined('MODX_CORE_PATH') || !defined('MODX_CONFIG_KEY')) {    
        print message("Could not load MODX.\n"
        ."MODX_CORE_PATH or MODX_CONFIG_KEY undefined in\n"
        ."{$dir}/config.core.php",'ERROR');
        die(2);
    }
    
    if (!file_exists(MODX_CORE_PATH.'model/modx/modx.class.php')) {
        print message("modx.class.php not found at ".MODX_CORE_PATH,'ERROR');
        die(3);
    }
}
// fire up MODX
find_modx();
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');

// get args from cli
$params = parse_args($argv);

if ($params['help']) {
    show_help();
    exit;
}

// Validate the args:
if(!is_numeric($params['sleep'])) {
     print message("--sleep must be a number",'ERROR');
     die();
}
if ($params['where']) {
    $where = json_decode($params['where'],true);
    if (!is_array($where)) {
        print message("--where must define a JSON array.",'ERROR');
        die();
    }
}

$c = $modx->newQuery('modResource');
if ($where) {
    $c->where($where);
}
if ($params['limit']) {
    $c->limit($params['limit']); 
}
if ($params['debug']) {
    print message("Debug SQL query.",'HELP');
    $c->prepare();
    print $c->toSQL();
    print "\n";
    exit;
}
// see http://www.webhostingtalk.com/showthread.php?t=1043707
// http://www.webdeveloper.com/forum/showthread.php?208676-Remote-site-loading-time
print "STARTING ".date('Y-m-d H:i:s'). "\n";
$mtime = explode(" ", microtime());
$tstart = $mtime[1] + $mtime[0];
$pg_cnt=0;
$e_cnt=0;
$collection = $modx->getIterator('modResource',$c);
foreach ($collection as $obj) {
    $url = $modx->makeUrl($obj->get('id'),'','','full');
    if (!$url) {
        print 'ERROR empty URL for page '.$obj->get('id')."\n";
        continue;
    }
    if (!$loadtime = request_url($url)) {
        print 'ERROR requesting '.$url."\n";
        $e_cnt++;
    }
    else {
        print $url . ' Load Time: '.$loadtime."s\n";
        $pg_cnt++;
    }
    wait($params['sleep']);
}

$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);
print "COMPLETE ".date('Y-m-d H:i:s'). "\n";
print "Total Time ".$totalTime."s\n"; // seconds
print "Total pages: ".$pg_cnt."\n";
print "Total errors: ".$e_cnt."\n";