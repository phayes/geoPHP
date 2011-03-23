GeoPHP is a native PHP library for doing basic geometry operations. It is written entirely in PHP and 
can therefore run on shared hosts. It is based on the Mapfish project by Camptocamp and is BSD licensed.

It is not meant to be high-performance nor is it meant to be an extensive implementation of the spec. 
If you have root on your machine and are looking for a high-performance PHP library for doing geometric 
operations, check out the GEOS PHP extension.

This project is currently looking for co-maintainers. If you think you can help out, please send me a 
message. Forks are also welcome, please issue pull requests and I will merge them into the main branch.



Long Terms Goals
-------------------------------------------------

The long-term goal of this project is to enable the full OpenGIS Simple Features Specification For SQL 
in PHP. We will optionally 'wrap' the geos-php extention so that applications can get a transparent 
'speed-up' when geos-php is installed on the server. This means that an application can use geoPHP 
to transparently enable geometry operations on both shared-hosts (via native PHP) and optimized 
servers (via geos-php) without the headache of switching libraries depending on the server environment.

We will start by implementing the most common methods and geometry-types, and wrapping the geos-php 
extention for the rest. This means that applications can get a useful "core-set" of geometry operations 
that work in all environments, and an "extended-set" of operations for environments that have geos-php
enabled. As time and resources allow we will be porting as much as possible to native PHP to enable
more operations on hosts without goes-php.

  

Example usage:
-------------------------------------------------

include_once('geoPHP.inc');

// Polygon WKT example
$polygon = geoPHP::load('POLYGON((1 1,5 1,5 5,1 5,1 1),(2 2,2 3,3 3,3 2,2 2))','wkt');
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

$multipoint = geoPHP::load($json, 'json');
$multipoint_points = $multipoint->getComponents();
$num_points = count($multipoint_points);
$first_wkt = $multipoint_points[0]->out('wkt');

print "This multipolygon has ".$num_points." points. The first point
has a wkt representation of ".$first_wkt;



API
-------------------------------------------------

Adapters
 - Methods
   * read               Read from adapter format and return a geometry object
   * write              Read a geometry object and return adapter format
 - Instances
   * WKT                Enables reading and writing WKT
   * GeoJSON            Enables reading and writing GeoJSON
   * KML                Enables reading and writing KML (Google Earth)

Geometry
 - Methods
   * getCentroid        returns Point geometry
   * getArea            returns area
   * getBBox            returns bouding box array
   * getGeomType        get the geometry type
   * out                writes to specified adapter format
 - Instances            
   * Point              
     - Methods          
       * getX           Get the X or longitude
       * getY           Get the Y or latitude
   * Collection
     - Methods
       * getComponents  Get the member geometry components
     - Instances
       * LineString
         - Instances
           * LinearRing
       * MultiLineString
       * MultiPoint
       * MultiPolygon
       * Polygon
       * GeometryCollection


Credit
-------------------------------------------------

Maintainer: Patrick Hayes
Code From:  sfMapFish Plugin by camptocamp (www.camptocamp.com)
            CIS by GeoMemes Research (www.geomemes.com)
            gisconverter.php by Arnaud Renevier (https://github.com/arenevier/gisconverter.php)
            
Where code from other projects or authors is included, those authors are included
in the copyright notice in that file
