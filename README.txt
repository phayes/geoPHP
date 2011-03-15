GeoPHP is a native PHP library for doing basic geometry operations. It is written entirely in PHP and can therefore run on shared hosts. It is based on the Mapfish project by Camptocamp and is BSD licensed.

It is not meant to be high-performance nor is it meant to be an extensive implementation of the spec. If you have root on your machine and are looking for a high-performance PHP library for doing geometric operations, check out the GEOS PHP extension.

This project is currently looking for co-maintainers. If you think you can help out, please send me a message. Forks are also welcome, please issue pull requests and I will merge them into the main branch.


Example usage:
-------------------------------------------------

include_once('geoPHP.inc');
$loader = new GeometryLoader();

// Polygon WKT example
$polygon = $loader->load('POLYGON((1 1,5 1,5 5,1 5,1 1),(2 2,2 3,3 3,3 2,2
2))','wkt');
$area = $polygon->getArea();
$centroid = $polygon->getCentroid();
$centX = $centroid->getX();
$centY = $centroid->getY();

print "This polygon has an area of ".$area." and a centroid with X=".$centX."
and Y=".$centY;

// MultiPoint json example
print "<br/>";
$json = 
'{
   "type": "MultiPoint",
   "coordinates": [
       [100.0, 0.0], [101.0, 1.0]
   ]
}';

$multipoint = $loader->load($json, 'json');
$multipoint_points = $multipoint->getComponents();
$num_points = count($multipoint_points);
$first_wkt = $multipoint_points[0]->out('wkt');

print "This multipolygon has ".$num_points." points. The first point
has a wkt representation of ".$first_wkt;



Credit
-------------------------------------------------

* The original authors of much of this code is camptocamp (www.camptocamp.com).

* It has been modified and extended by Patrick Hayes.

