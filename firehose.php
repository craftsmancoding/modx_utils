<?php
/**
*  Firehose via CLI 
*  Quickly create objects for modx
*  Supported class name : modResource, modUser, modChunk, modSnippet, modPlugin
*
*  PARAMETERS
*  --class_name which type of object are we creating. Default: modResource
*  --count how many objects should be created? Default: 10
*  --remove remove firehose records
*  You can use other Object Field as a parameter like --published==1 or --hidemenu=1 or --longtitle="Sample Long Title"
*
* SAMPLE USAGE:
*
* Run the script via the command line.  The simplest invocation is to just run the 
* script without any options: this will create 10 records for modResource Class
*
* 		php firehose.php
*
* You can supply options when you run the script
*  
*      php firehose.php --class_name=modUser
*
* To set count of objects to be created, LIMIT is 200 records
*
*      php firehose.php --count=100
*
* To Delete Firehose records, default to modResource
*
*     php firehose.php --remove
*
* To Delete Firehose records of specific class_name
*
*     php firehose.php --remove --class_name=modUser
*
*
* AUTHOR:
* Daniel Edano (daniel@craftsmancoding.com)
*
**/

if (php_sapi_name() !== 'cli') {
    error_log('Firehose CLI script can only be executed from the command line.');
    die('CLI access only.');
}

// Find MODX...

// As long as this script is built placed inside a MODX docroot, this will sniff out
// a valid MODX_CORE_PATH.  This will effectively force the MODX_CONFIG_KEY too.
// The config key controls which config file will be loaded. 
// Syntax: {$config_key}.inc.php
// 99.9% of the time this will be "config", but it's useful when dealing with
// dev/prod pushes to have a config.inc.php and a prod.inc.php, stg.inc.php etc.
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
$modx = new modx();
$modx->initialize('mgr');

// get args from cli
$args = get_args();


// remove firehose records
if($args['remove']) {

    print 'Are you sure you want to delete all Firehose Records? (y/n) [n] > ';
    $yn = strtolower(trim(fgets(STDIN)));
    if ($yn!='y') {
        die();
    }

    switch ($args['class_name']) {
        case 'modResource':
            $records = filter_records('modResource');
            break;
        case 'modUser':
            $records = filter_records('modUser','username');
            
            break;
        case 'modChunk':
            $records = filter_records('modChunk','name');
            break;
        case 'modSnippet':
             $records = filter_records('modSnippet','name');
            break;
        case 'modPlugin':
            $records = filter_records('modPlugin','name');
            break;
        default:
            print message('Unsupported Class Name','ERROR');
            die();
    }

    if (count($records) == 0) {
        print message("No Firehose Records Found",'ERROR');
        die();
    };


    foreach ($records as $r) {
        if ($r->remove() == false) {
            print message("Failed to delete a Record",'ERROR');
            die();
        }
    }
    print message("Sample records were Successfully Deleted.",'SUCCESS');
    die();
}

// if count is 0 exit
if($args['count'] == 0 || $args['count'] > 200) {
     print message("No Records Affected. COUNT Error",'ERROR');
     die();
}


// perform action for specific class name
switch ($args['class_name']) {
    case 'modResource':
        for ($i=1; $i <= $args['count'] ; $i++) {    
            $obj = $modx->newObject('modResource');
            // set modx fields from cli args
            set_fields(parse_args($argv),$obj);

            // set/overrides main fields
            $content = generate_lorem(150);
            $pagetitle = preg_split('/[\s,]+/', $content, 3);
            //remove 3rd Item
            array_pop($pagetitle);
            $obj->set('pagetitle', "Firehose_$i " . ucwords(implode(' ', $pagetitle)) );
            $obj->set('alias', "Firehose_$i-" .implode('-', $pagetitle) );
            $obj->set('content', ucfirst($content) );

            if (!$obj->save()) {
                print message("Failed to add a Record",'ERROR');
                die();
            }
        }
        print message("Sample Pages were Successfully Created.",'SUCCESS');
        die();
        break;
    case 'modUser' : 
         for ($i=1; $i <= $args['count'] ; $i++) {
            $username = "Firehose_$i-".str_replace(' ', '_', generate_lorem(2));

            $user = $modx->newObject('modUser');
            $profile = $modx->newObject('modUserProfile');

            set_fields(parse_args($argv),$user);
            set_fields(parse_args($argv),$profile);
            // force a value
            $user->set('username',$username);
            $user->set('password',$username );
            $profile->set('internalKey',0);

            $user->addOne($profile,'Profile');

            // save user
            if (!$user->save()) {
                print message("Failed to add a User",'ERROR');
                die();
            }
        }
        print message("Sample Users were Successfully Created.",'SUCCESS');
        break;
    case 'modChunk':
        for ($i=1; $i <= $args['count'] ; $i++) { 
            $chunk = generate_lorem(50);
            $name = preg_split('/[\s,]+/', $chunk, 3);
            //remove 3rd Item
            array_pop($name);

            $ch = $modx->newObject('modChunk');

            set_fields(parse_args($argv),$ch);
            $ch->set('name', "Firehose_$i" . str_replace(' ', '_', implode(' ', $name)) );
            $ch->set('snippet', '<p>'.ucfirst($chunk).'</p>' );

            $ch->save();
            if (!$ch->save()) {
                print message("Failed to add a Chunk",'ERROR');
                die();
            }
        }
        print message("Sample Chunks were Successfully Created.",'SUCCESS');
        die();
        break;
    case 'modSnippet':
        for ($i=1; $i <= $args['count'] ; $i++) { 
            $snippet_name = generate_lorem(20);
            $name = preg_split('/[\s,]+/', $snippet_name, 3);
            //remove 3rd Item
            array_pop($name);

            $snippet = $modx->newObject('modSnippet');
            set_fields(parse_args($argv),$snippet);
            $snippet->set('name', "Firehose_$i" . str_replace(' ', '_', implode(' ', $name)) );
            $snippet->set('snippet', 'esnippeto '."'".ucfirst($snippet_name)."';" );

            $snippet->save();
            if (!$snippet->save()) {
                print message("Failed to add a Snippet",'ERROR');
                die();
            }
        }
        print message("Sample Snippet were Successfully Created.",'SUCCESS');
        die();
        break;
    case 'modPlugin':
        for ($i=1; $i <= $args['count'] ; $i++) { 
            $plugin_code = array();
            generate_lorem($plugin_code, 20);
            $name = preg_split('/[\s,]+/', $plugin_code, 3);
            //remove 3rd Item
            array_pop($name);

            $plugin = $modx->newObject('modPlugin');
            set_fields(parse_args($argv),$plugin);
            $plugin->set('name', "Firehose_$i" . str_replace(' ', '_', implode(' ', $name)) );
            $plugin->set('plugincode', 'echo '."'".ucfirst($plugin_code)."';" );

            $plugin->save();
            if (!$plugin->save()) {
                print message("Failed to add a Plugin",'ERROR');
                die();
            }
        }
        print message("Sample Plugin were Successfully Created.",'SUCCESS');
        die();
        break;
    default:
        print message('Unsupported Class Name','ERROR');
        die();
}




/**
 * Get and vet command line arguments
 * @return array
 */
function get_args() {
	$shortopts  = '';
	$shortopts .= 'n::'; // Optional value
	$shortopts .= 'c::'; // Optional value
    $shortopts .= 'r'; // Optional value
    $shortopts .= 'h'; // Optional value
	
	$longopts  = array(
	    'class_name::',        // Optional value
	    'count::',           // Optional value
        'remove',           // Optional value
        'help',           // Optional value
	);
	
	$opts = getopt($shortopts, $longopts);

    if (isset($opts['help'])) {
        show_help();die();
    }

	$opts['class_name'] = !isset($opts['class_name']) ? 'modResource' : $opts['class_name'];
	$opts['count'] = !isset($opts['count']) ? 10 : (int) $opts['count'];
    $opts['remove'] = !isset($opts['remove']) ? 0 : 1;

	
	return $opts;
}


/**
* filter_records
* query records using class name and field
* @param string $class_name
* @param string $field
* @return empty array or $object
**/
function filter_records($class_name,$field='pagetitle') {
    global $modx;
    if(empty($class_name)) {
        return array();
    }
    $c = $modx->newQuery($class_name);
    $c->where(array(
       "$field:LIKE" => 'Firehose_%',
    ));
    return $modx->getCollection($class_name,$c);
}

/**
* generate_lorem : generate random length of words
* @param int count
* @return string random words
**/
function generate_lorem($count)
{
    $random_words = array();
    $words = array('lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit','curabitur','vel','hendrerit','libero','eleifend','blandit','nunc','ornare','odio','ut','orci','gravida','imperdiet','nullam','purus','lacinia','a','pretium','quis','congue','praesent','sagittis','laoreet','auctor','mauris','non','velit','eros','dictum','proin','accumsan','sapien','nec','massa','volutpat','venenatis','sed','eu','molestie','lacus','quisque','porttitor','ligula','dui','mollis','tempus','at','magna','vestibulum','turpis','ac','diam','tincidunt','id','condimentum','enim','sodales','in','hac','habitasse','platea','dictumst','aenean','neque','fusce','augue','leo','eget','semper','mattis','tortor','scelerisque','nulla','interdum','tellus','malesuada','rhoncus','porta','sem','aliquet','et','nam','suspendisse','potenti','vivamus','luctus','fringilla','erat','donec','justo','vehicula','ultricies','varius','ante','primis','faucibus','ultrices','posuere','cubilia','curae','etiam','cursus','aliquam','quam','dapibus',
        'nisl','feugiat','egestas','class','aptent','taciti','sociosqu','ad','litora','torquent','per','conubia','nostra','inceptos','himenaeos','phasellus','nibh','pulvinar','vitae','urna','iaculis','lobortis','nisi','viverra','arcu','morbi','pellentesque','metus','commodo','ut','facilisis','felis','tristique','ullamcorper','placerat','aenean','convallis','sollicitudin','integer','rutrum','duis','est','etiam','bibendum','donec','pharetra','vulputate','maecenas','mi','fermentum','consequat','suscipit','aliquam','habitant','senectus','netus','fames','quisque','euismod','curabitur','lectus','elementum','tempor','risus','cras' );

    $i = 0;
    
    for($i; $i < $count; $i++)
    {
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
* parse_args function : parse arguments used from command line
* remove --
* @param array $argv
* @return array $data : multi array with possible modx field and value
***/
function parse_args($argv){
    $data = array();
    foreach ($argv as $key => $value) {
        if (strpos($value,'--') !== false) {
            $value = str_replace('--', '', $value);
            $data[] = explode('=', $value);    
        }
    }
    return $data;
}


/**
* Reusable function
* set_fields : Set modx fields using the parsed arguments from cli
* @param array $data : parsed_args
* @param obj $obj : modx obj
**/
function set_fields($data,$obj) {
   if(empty($data)) {
        return;
   }
   foreach ($data as $field) {
        $obj->set($field[0], isset($field[1]) ? $field[1] : '');
   }
}

/**
* display help
**/
function show_help() {

    print "
----------------------------------------------
Firehose via CLI 
----------------------------------------------
This Utility Quickly create objects for modx 
Supported class name : modResource, modUser, modChunk, modSnippet, modPlugin
----------------------------------------------
PARAMETERS:
----------------------------------------------
--class_name which type of object are we creating. Default: modResource
--count how many objects should be created? Default: 10
--remove remove firehose records
--help : displays this help page.

----------------------------------------------
USAGE EXAMPLES:
* Run the script via the command line.
----------------------------------------------
php ".basename(__FILE__)."

    The simplest invocation is to just run the script without any options: this will create 10 records for modResource Class

php ".basename(__FILE__)." --class_name=modUser

    You can supply --class_name option when you run the script

php ".basename(__FILE__)." --count=100

    --count set count of objects to be created, LIMIT is 200 records

php ".basename(__FILE__)." --remove

    Use --remove to Delete Firehose records, default to modResource

php ".basename(__FILE__)." --remove --class_name=modUser

    To Delete Firehose records of specific class_name, add --class_name

----------------------------------------------
EXTRA Parameters
----------------------------------------------
* You can use other Object Field as a parameter like --published==1 or --hidemenu=1 or --longtitle=Sample Long Title
*
";
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
 * @return string
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
            $out = '[46m HELP: '. chr(27).'[0;34m '; //Blue
            break;
        default:
            throw new Exception('Invalid status: ' . $status);
    }
    return "\n".chr(27) . $out . $text .' '. chr(27) . '[0m'."\n\n";
}