Example:


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
$number_of_points = count($multipoint_points);
$first_wkt = $multipoint_points[0]->out('wkt');

print "This multipolygon has ".$multipoint_points." points. The first point
has a wkt representation of ".$first_wkt;
