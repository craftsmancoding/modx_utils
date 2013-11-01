<?php
/**
 * Recursively tests a directory for bad PHP files.
 * Run from the command line only.
 *
 * WARNING: some hosts disable paramters necessary to run PHP from the 
 * command-line, e.g. $argv, etc.  Other servers use different versions
 * of PHP on the command-line (vs. on the web).  These behaviors may 
 * result in false-positives (e.g. syntaxes supported in one version, but
 * not in another), or they may prevent you from running this script entirely.
 * If needed, edit this file and set the $dir variable directly.
 *
 * USAGE:
 *
 *  php sniff.php /path/to/dir
 *
 * Author: everett@craftsmancoding.com  10/31/2013
 */
 
// Override this if your host has squelched command-line arguments.
$dir = '';
 
//------------------------------------------------------------------------------
//! Functions
//------------------------------------------------------------------------------
function check_php_syntax($dir) {
    global $syntax_errors;
    
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    check_php_syntax($dir.'/'.$object); 
                }
                elseif(substr($object,-4) == '.php') {
                    //$x = `php -d error_reporting=0 -l {$dir}/{$object}`;
                    $x = `php -d error_reporting=1 -l {$dir}/{$object}`;
                    if (substr($x,0,25) != 'No syntax errors detected') {
                        $syntax_errors[] = $dir.'/'.$object;
                    }
                }
            }
        }
        reset($objects);
    }
}

function help($msg) {
    print $msg."\n\n";
    print "USAGE:\n";
    print "    php sniff.php path/to/dir";
    print "\n\n";
    exit(1);
} 
//------------------------------------------------------------------------------
//! MAIN
//------------------------------------------------------------------------------

$syntax_errors = array();

if (empty($dir)) {
    if (!isset($argv[1])) {
        help("Please supply a valid directory.
        (Warning: some hosts disable command-line arguments -- edit this file and set \$dir manually).");
    }
    if (!file_exists($argv[1])) {
        help($argv[1].' does not exist.');
    }
    if (!is_dir($argv[1])) {
        help($argv[1].' is not a directory.');
    }
    
    $dir = $argv[1];
}

check_php_syntax($dir);

if (empty($syntax_errors)) {
    print "SUCCESS.  No syntax errors detected in any PHP files in {$argv[1]}\n";
}
else {
    print "ERRORS DETECTED!\n";
    print "There were PHP syntax errors discovered in the following files:\n";
    print implode("\n",$syntax_errors);
    print "\n";
}
 
/*EOF*/