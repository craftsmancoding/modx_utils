#!/usr/bin/env php
<?php
/**
 * Sitemap Submitter
 *
 * Use this script to submit your site maps automatically to:
 *      - Google
 *      - Bing.MSN 
 *      - Ask
 *
 * Usage:
 *
 *  php /path/to/this/sitemap.php http://siteone.com/sitemap.xml http://otheriste.com/sitemap.xml
 *
 * Any argument passed to this script should be a valid URL to your sitemap.xml.
 *
 * Reference in a Crontab:

# As a reference, here's your legend
# .---------------- minute (0 - 59)
# | .------------- hour (0 - 23)
# | | .---------- day of month (1 - 31)
# | | | .------- month (1 - 12) OR jan,feb,mar,apr ...
# | | | | .---- day of week (0 - 6) (Sunday=0 or 7)  OR sun,mon,tue,wed,thu,fri,sat
# | | | | |
# * * * * *  command to be executed

  0 2 * * 1 php /path/to/sitemap.php http://siteone.com/sitemap.xml http://otheriste.com/sitemap.xml >> /path/to/sitemap.log


 * Adapted from 
 * http://www.benhallbenhall.com/2013/01/script-automatically-submit-sitemap-google-bing-yahoo-ask-etc/
 * by everett@fireproofsocks.com  May 2014
 */

if (php_sapi_name() !== 'cli') {
    error_log('This CLI script can only be executed from the command line.');
    die('CLI access only.');
}

// cUrl handler to ping the Sitemap submission URLs for Search Engines
function myCurl($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

echo "Starting site submission at ".date('Y-m-d H:i:s')."\n";

// $argv[0] contains the name of this script.
// We just want the args, so we skip the script name
array_shift($argv);
foreach ($argv as $s) {
    if(filter_var($s, FILTER_VALIDATE_URL) === false) {
        echo "Invalid URL: ".escapeshellarg($s)." -- skipping\n";
        continue;
    }
    echo "Submitting $s\n";    
    continue;
    //Google
    $url = "http://www.google.com/webmasters/sitemaps/ping?sitemap=".$s;
    $returnCode = myCurl($url);
    echo "Google Sitemaps has been pinged (return code: {$returnCode}).\n";
     
    //Bing / MSN
    $url = "http://www.bing.com/webmaster/ping.aspx?siteMap=".$s;
    myCurl($url);
    echo "Bing / MSN Sitemaps has been pinged (return code: {$returnCode}).\n";
    
    //ASK
    $url = "http://submissions.ask.com/ping?sitemap=".$s;
    myCurl($url);
    echo "ASK.com Sitemaps has been pinged (return code: $returnCode).\n";
}

echo "Completed at ".date('Y-m-d H:i:s')."\n";