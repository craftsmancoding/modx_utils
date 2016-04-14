<?php
/**
 * modCLI is a command line interface for MODX Revolution Processors.
 *
 * It enables you to run any core or third party processor from the command line, passing it options as you go along. 
 *
 * @author Mark Hamstra <hello@markhamstra.com>
 * @version 0.1.1-pl, 2013-07-26
 * @license GPL v2
 * @source https://gist.github.com/Mark-H/4303088
 */
 
// Make sure we're on the command line.
if (realpath($_SERVER['argv'][0]) != __FILE__)
{
die('This is CLI script! You can not run it from the web!');
}
define('MODCLI_VERSION','0.1.0-pl');
/**
 * Format keys (passed by reference) padded to $padLength with spaces and ellepsis.
 * @param $key
 * @param int $padLength
 */
function formatKey(&$key, $padLength = 15) {
    if (strlen($key) > 15) $key = substr($key, 0, 13) . "..";
    $key = str_pad($key, 15);
}
/**
 * Format values (passed by reference) limited to $maxLength characters, with a $prefix and $prePad amount of spaces.
 * @param $value
 * @param int $maxLength
 * @param string $prefix
 * @param int $prePad
 * @param bool $preBreakIfMultiline
 */
function formatValue(&$value, $maxLength = 200, $prefix = '>', $prePad = 6, $preBreakIfMultiline = true) {
    if (strlen($value) > $maxLength) {
        $value = substr($value, 0, $maxLength - 3) . "...";
    }
    $value = str_replace("\n", "\n" . $prefix . str_repeat(' ', $prePad), $value);
    if ($preBreakIfMultiline && substr_count($value, "\n") > 0) {
        $value = "\n" . $prefix . str_repeat(' ', $prePad) . $value;
    }
}
// include modX
define('MODX_API_MODE', true);
require_once(dirname(__FILE__) . '/index.php');
$modx= new modX();
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');
/* Ensure log entries are echo-ed straight to the console */
$modx->setLogTarget('ECHO');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
//  Get the cli options
$scriptProperties = array();
$processor = $argv[1];
parse_str(implode('&', array_slice($argv, 2)), $scriptProperties);
$debug = (in_array('debug', $argv));
// Show some infos.
echo "# Running modCLI ".MODCLI_VERSION." in {$argv[0]}\n";
if ($debug) {
    echo "# Debug is enabled.\n";
}
// Make sure we have a processor to call, otherwise display a bit of help.
if (empty($processor) || in_array($processor, array('--help', '-help', '-h', '-?'))) {
    echo "modCLI Usage: \n\tphp {$argv[0]} <processor> [[field=value] [field=value] ...]
\t\t where <processor> is a valid core processor.\n\n";
      exit(1);
}
// Show processor name to user.
echo "# Processor: {$processor}\n";
// Allow overriding of the processors_path; this allows running 3rd party processors too. 
$options = array();
if (isset($scriptProperties['processors_path']) && !empty($scriptProperties['processors_path'])) {
    $options['processors_path'] = $scriptProperties['processors_path'];
    echo "# Processor Path: {$options['processors_path']} \n";
}
// Show the properties we are passing (if any).
if (!empty($scriptProperties)) {
    echo "# Properties: \n";
    foreach ($scriptProperties as $key => $value) {
        // Get rid of some unneeded properties
        if (in_array($key, array('processors_path', 'debug'))) {
            unset($scriptProperties[$key]);
            continue;
        }
        formatKey($key);
        formatValue($value, 200, '#');
        // Show the property on screen.
        echo "#    {$key} => {$value} \n";
    }
}
echo "\n";
// Run the processor.
$result = $modx->runProcessor($processor, $scriptProperties, $options);
// If the $result is a modProcessorResponse, it was a valid processor. Otherwise not so much.
if ($result instanceof modProcessorResponse) {
    // Get the raw response
    $response = $result->getResponse();
    // If it's not an array yet, it may be a JSON collection. Try that.
    if (!is_array($response)) {
        $responseFromJSON = $modx->fromJSON($response);
        if ($responseFromJSON !== false) {
            $response = $responseFromJSON;
        }
    }
    if ($debug) {
        echo "> Raw response: " . print_r($response, true) . "\n";
    }
    // Dealing with a "get" processor.
    if ($result->hasObject()) {
        if ($debug) echo "> Response type: get \n";
        // Get the returned object.
        $object = $result->getObject();
        echo "> Object retrieved: \n";
        // Output object properties
        foreach ($object as $key => $value) {
            formatKey($key);
            formatValue($value, 200, '>', 8);
            echo ">    {$key} => {$value}\n";
        }
    } 
    // Dealing with a "getlist" processor
    elseif (isset($response['total']) && is_numeric($response['total']) && isset($response['results'])) {
        if ($debug) echo "> Response type: getlist \n";
        echo "> Amount of Results: {$response['total']} \n";
        foreach ($response['results'] as $idx => $item) {
            // Increase idx by 1 to make it more human-like.
            $idx++;
            echo ">   Result {$idx}:\n";
            foreach ($item as $key => $value) {
                // Nicely format the key.
                formatKey($key);
                formatValue($value, 100, '>', 8);
                echo ">      {$key} => {$value}\n";
            }
        }
    } 
    // Dealing with a more "do-y" kind of processor with success or error
    else {
        if ($debug) echo "> Response type: do-y stuff \n";
        if ($result->isError()) {
            echo "Uh oh, something went wrong. \n";
            // Show errors if we have them.
            $fieldErrors = $result->getAllErrors();
            if (!empty($fieldErrors)) {
                echo "Errors:\n";
                foreach ($fieldErrors as $error) {
                    echo "   {$error} \n"; 
                }
            }
            echo "\n";
            exit(1);
        }
    }
} else {
    echo "Error: Processor not found. \n\n";
    exit (1);
}
echo "\n";
exit(0);
