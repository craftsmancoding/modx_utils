<?php
/**
 * Firehose 
 * Quickly create objects for modx Revolution via the CLI.
 * Supported class names : modResource, modUser, modChunk, modSnippet, modPlugin
 *
 * INSTALLATION:
 * Copy this file in its entirety somewhere into the webroot of your MODX Revo site.
 * Alternatively, use wget to download the file directly from github:
 *
 *  wget https://raw.githubusercontent.com/craftsmancoding/modx_utils/master/firehose.php
 *
 * USAGE:
 * See --help for usage.
 *
 * AUTHOR:
 * Daniel Edano (daniel@craftsmancoding.com)
 */
// This can only be run via cli
if (php_sapi_name() !== 'cli') {
    error_log('Firehose CLI script can only be executed from the command line.');
    die('CLI access only.');
}
$supported_classnames = array('modResource'=>'pagetitle','modUser'=>'username','modChunk'=>'name','modPlugin'=>'name','modSnippet'=>'name');

/**
 * Add records to the database
 * @param array $args 
 */
function add_records($args) {
    global $modx;
    for ($i=1; $i <= $args['count'] ; $i++) {  
        $obj = $modx->newObject($args['classname']);
        $obj->fromArray($args);
        switch ($args['classname']) {
            case 'modResource':
                // Set the things that need to be unique
                // and the things that must be set.
                $pagetitle = "Firehose_".uniqid();
                $obj->set('pagetitle', $pagetitle);
                $obj->set('alias', $pagetitle);
                $obj->set('content', ucfirst(generate_lorem(150)));
                break;
            case 'modChunk' :
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('snippet', '<p>'.ucfirst(generate_lorem(50)).'</p>');
                break;
            case 'modSnippet':
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('snippet', 'echo '."'".ucfirst(generate_lorem(20))."';");
                break;
            case 'modPlugin':
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('plugincode', 'echo '."'".ucfirst(generate_lorem(20))."';");
                break;
            case 'modUser'  :
                $username = "Firehose_".uniqid();
                $profile = $modx->newObject('modUserProfile');

                $profile->set('email','test@test.com');
                $profile->fromArray($args);

                // force a value
                $obj->set('username',$username);
                $obj->set('password',$username );
                $profile->set('internalKey',0);

                $obj->addOne($profile,'Profile');
                break;
        }
        if (!$obj->save()) {
            print message("Failed to add a {$args['classname']} Record",'ERROR');
        }
    }
}


/**
 * generate random length of words
 *
 * @param int count
 * @return string random words
 */
function generate_lorem($count) {
    $random_words = array();
    $words = array('lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit','curabitur','vel','hendrerit','libero','eleifend','blandit','nunc','ornare','odio','ut','orci','gravida','imperdiet','nullam','purus','lacinia','a','pretium','quis','congue','praesent','sagittis','laoreet','auctor','mauris','non','velit','eros','dictum','proin','accumsan','sapien','nec','massa','volutpat','venenatis','sed','eu','molestie','lacus','quisque','porttitor','ligula','dui','mollis','tempus','at','magna','vestibulum','turpis','ac','diam','tincidunt','id','condimentum','enim','sodales','in','hac','habitasse','platea','dictumst','aenean','neque','fusce','augue','leo','eget','semper','mattis','tortor','scelerisque','nulla','interdum','tellus','malesuada','rhoncus','porta','sem','aliquet','et','nam','suspendisse','potenti','vivamus','luctus','fringilla','erat','donec','justo','vehicula','ultricies','varius','ante','primis','faucibus','ultrices','posuere','cubilia','curae','etiam','cursus','aliquam','quam','dapibus',
        'nisl','feugiat','egestas','class','aptent','taciti','sociosqu','ad','litora','torquent','per','conubia','nostra','inceptos','himenaeos','phasellus','nibh','pulvinar','vitae','urna','iaculis','lobortis','nisi','viverra','arcu','morbi','pellentesque','metus','commodo','ut','facilisis','felis','tristique','ullamcorper','placerat','aenean','convallis','sollicitudin','integer','rutrum','duis','est','etiam','bibendum','donec','pharetra','vulputate','maecenas','mi','fermentum','consequat','suscipit','aliquam','habitant','senectus','netus','fames','quisque','euismod','curabitur','lectus','elementum','tempor','risus','cras' );
    $i = 0;    
    for($i; $i < $count; $i++) {
        $index = array_rand($words);
        $word = $words[$index];
        //echo $index . '=>' . $word . '<br />';
        
        if($i > 0 && $random_words[$i - 1] == $word)
            $i--;
        else
            $random_words[$i] = $word;
    }
    return  implode(' ', $random_words);
}

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
        'count' => 10,
        'remove' => false,
        'classname' => '',
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
 * Remove records from the database
 * @param string $classname
 * @return integer count of the removed items
 */
function remove_records($classname) {
    global $modx;
    global $supported_classnames;

    // Default is to delete all records that firehose added
    // but if classname is set, then we only remove records from that table
    if (!empty($classname) && array_key_exists($classname, $supported_classnames)) {
        $supported_classnames = array($classname=>$supported_classnames[$classname]); 
    }
    $cnt = 0;
    foreach ($supported_classnames as $classname => $field) {
        $c = $modx->newQuery($classname);
        $c->where(array(
           "$field:LIKE" => 'Firehose_%',
        ));
        $collection = $modx->getIterator($classname,$c);
        foreach ($collection as $obj) {
            if ($obj->remove() == false) {
                print message("Failed to delete a {$classname} Record with field ".$obj->get($field),'ERROR');
            }
            $cnt++;
        }
    }
    
    return $cnt;
}


/**
 * display help message
 */
function show_help() {

    print message('Firehose','HELP');
    print "Command line utility for quickly adding lots of sample records to MODX Revolution.
    
".message('PARAMETERS:','HEADER')."
 --classname    Identify the type of object to create or delete. Required for insert operations.
                Supported class names: modResource, modUser, modChunk, modSnippet, modPlugin
 --count        how many objects should be created? (Default: 10)
 --remove       remove all records created with firehose. Specify --classname to restrict deletion.
 --help         displays this help page.
 ...more...     When adding records, you may specify any non-reserved object attribute relevant to 
                the classname being created.  E.g. --template for modResource or --category for modSnippet.
".message('USAGE EXAMPLES:','HEADER').
"Create 1000 pages:
    php ".basename(__FILE__)." --modResource --count=1000

Create 10 users:
    php ".basename(__FILE__)." --modUser --count=10

Specify extra parameters to create pages that are unpublished children of page 3
    php ".basename(__FILE__)." --modResource --published=0 --parent=3

Cleanup all records created by firehose:
    php ".basename(__FILE__)." --remove

Remove only modChunk records created by firehose:
    php ".basename(__FILE__)." --remove --classname=modChunk

";
}
//------------------------------------------------------------------------------
//! MAIN BLOCK
//------------------------------------------------------------------------------
// Find MODX: as long this script is inside a MODX webroot, it will run.
$dir = '';
if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
    $max = 10;
    $i = 0;
    $dir = dirname(__FILE__);
    while(true) {
        if (file_exists($dir.'/config.core.php')) {
            include $dir.'/config.core.php';
            break;
        }
        $i++;
        $dir = dirname($dir);
        if ($i >= $max) {
            print message("Could not find a valid MODX config.core.php file.\n"
            ."Make sure your repo is inside a MODX webroot and try again.",'ERROR');
            die(1);
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

// fire up MODX
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
if($params['count'] <= 0) {
     print message("--count must be greater than 0",'ERROR');
     die();
}
if (!$params['remove'] && !$params['classname']) {
     print message("--classname is required for insert operations.",'ERROR');
     die();    
}
if ($params['classname'] && !in_array( $params['classname'], array_keys($supported_classnames))) {
    print message("Unsupported classname.",'ERROR');
    die();
}

// do the action
if ($params['remove']) {
    $cnt = remove_records($params['classname']);
    print message("$cnt records removed.",'SUCCESS');
}
else {
    add_records($params);
    print message("{$params['count']} {$params['classname']} records created.",'SUCCESS');
}