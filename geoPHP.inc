<?php
/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once("lib/GeometryLoader.class.php");
include_once("lib/adapters/GeoAdapter.class.php");
include_once("lib/adapters/GeoJSON.class.php");
include_once("lib/adapters/WKT.class.php");
include_once("lib/adapters/KML.class.php");
include_once("lib/adapters/GoogleGeocode.class.php");
include_once("lib/geometry/Geometry.class.php");
include_once("lib/geometry/Point.class.php");
include_once("lib/geometry/Collection.class.php");
include_once("lib/geometry/LineString.class.php");
include_once("lib/geometry/MultiPoint.class.php");
include_once("lib/geometry/LinearRing.class.php");
include_once("lib/geometry/Polygon.class.php");
include_once("lib/geometry/MultiLineString.class.php");
include_once("lib/geometry/MultiPolygon.class.php");
include_once("lib/geometry/GeometryCollection.class.php");

class geoPHP {

  function version() {
  	return '0.2';
  }
  
  // geoPHPLoad($data, $type, $other_args);
  function load() {
  	
  	$args = func_get_args();
  	
  	$data = array_shift($args);
  	$type = array_shift($args);
  	
    $type_map = geoPHP::getAdapterMap();
    
    $processor_type = $type_map[$type];
    $processor = new $processor_type();
    
    // @@TODO: Hack alert!
    // There has got to be a better way to do this...
    // Is there an equivilent to call_user_func for objects???
    if (count($args) == 0) $result = $processor->read($data);
    if (count($args) == 1) $result = $processor->read($data, $args[0]);
    if (count($args) == 2) $result = $processor->read($data, $args[0],$args[1]);
    if (count($args) == 3) $result = $processor->read($data, $args[0],$args[1],$args[2]);
    if (count($args) == 4) $result = $processor->read($data, $args[0],$args[1],$args[2], $args[3]);
    if (count($args) == 5) $result = $processor->read($data, $args[0],$args[1],$args[2], $args[3], $args[4]);
    
    return $result;
  }
  
  function getAdapterMap() {
    return array (
      'wkt' => 'WKT',
      'json' => 'GeoJSON',
      'kml' => 'KML',
      'google_geocode' => 'GoogleGeocode',
    );
  }
  
}