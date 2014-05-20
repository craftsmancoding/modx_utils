<?php
/**
 * Deliberately slow and inefficient script to benchmark PHP's speed
 *
 */
// Script start
if (php_sapi_name() !== 'cli') {
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    print '<pre>';
}

print "Starting sript at ".date('Y-m-d H:i:') ."\n";
// set start time
$mtime = explode(" ", microtime());
$tstart = $mtime[1] + $mtime[0];

$rustart = getrusage();
 
// Deliberately inefficient code
$array = array_fill(0, 1000000, rand(0,1000000));
for ( $i=0; $i <= count($array); $i++ ) {
    // do nothing
}



// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

$ru = getrusage();
echo "This process used " . rutime($ru, $rustart, "utime") .
    " ms for its computations\n";
echo "It spent " . rutime($ru, $rustart, "stime") .
    " ms in system calls\n";
    
// how long did it take?
$mtime = explode(" ", microtime());
$tend = $mtime[1] + $mtime[0];
print 'It took '.sprintf("%2.4f s", ($tend - $tstart)) ." to execute\n";
    