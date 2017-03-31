<?php

/**
 * Some very simple performance test.
 * Run before and after you modify geometries and their methods.
 *
 * For example adding an array_merge() to a heavily used method can decrease performance by one magnitude.
 * Feel free to add more test methods.
 */

namespace geoPHP\Geometry;

use \geoPHP\geoPHP;

require '../vendor/autoload.php';

function testStart($message) {
    $GLOBALS['runTime'] = microtime(true);
    echo $message . "\n";
}
function testEnd($result=null, $ready=false) {
    if ($ready) {
        echo "\nTotal run time: " . round(microtime(true) - $GLOBALS['startTime'], 4) . ' sec,';
    } else {
        echo '  Time: ' . round(microtime(true) - $GLOBALS['runTime'], 4) . ' sec,';
    }
    echo
            ' Memory: ' . (memory_get_usage()/1024/1024 - $GLOBALS['startMem']) . 'MB' .
            ' Memory peak: ' . (memory_get_peak_usage()/1024/1024) . 'MB' .
            ($result ? ' Result: ' . $result : '') .
            "\n";
}

GeoPhp::geosInstalled(FALSE);


$startTime = microtime(true);
$startMem = memory_get_usage(true)/1024/1024;
$res=null;


/////////////////////////////////////////////////////////////////////////////////////

$pointCount = 10000;

testStart("Creating " . $pointCount . " EMPTY Point:");
/** @var Point[] $points */
$points = [];
for ($i=0; $i < $pointCount; $i++) {
    $points[] = new Point();
}
testEnd();

testStart("Creating " . $pointCount . " Point:");
$points = [];
for ($i=0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i+1);
}
testEnd();

testStart("Creating " . $pointCount . " PointZ:");
$points = [];
for ($i=0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i+1, $i+2);
}
testEnd();

testStart("Creating " . $pointCount . " PointZM:");
$points = [];
for ($i=0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i+1, $i+2, $i+3);
}
testEnd();

testStart("Test points Point::is3D():");
foreach($points as $point) {
    $point->is3D();
}
testEnd();

testStart("Adding points to LineString:");
$lineString = new LineString($points);
testEnd();

$smallLineString = new LineString(array_slice($points, 0, 1000));

testStart("Test (small) LineString::isSimple():");
$smallLineString->isSimple();
testEnd();

testStart("Test LineString::getComponents() points isMeasured():");
foreach($lineString->getComponents() as $point) {
    $point->isMeasured();
}
testEnd();

testStart("Test LineString::explode(true) 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = count($lineString->explode(true));
}
testEnd($res . ' segment');

testStart("Test LineString::explode() 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = count($lineString->explode());
}
testEnd($res . ' segment');

testStart("Test LineString::length() 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = $lineString->length();
}
testEnd($res);

testStart("Test LineString::greatCircleLength() 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = $lineString->greatCircleLength();
}
testEnd($res);

testStart("Test LineString::haversineLength() 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = $lineString->haversineLength();
}
testEnd($res);

testStart("Test LineString::vincentyLength() 10 times:");
for($i=1; $i <= 10; $i++) {
    $res = $lineString->vincentyLength();
}
testEnd($res);




//////////////////////////////////////////////////////////////////////////

testEnd(null, true);
