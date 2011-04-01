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
    
  function load($data, $type) {
    $type_map = geoPHP::getAdapterMap();
    
    $processor_type = $type_map[$type];
    $processor = new $processor_type();
    
    return $processor->read($data);
  }
  
  function getAdapterMap() {
    return array (
      'wkt' => 'WKT',
      'json' => 'GeoJSON',
      'kml' => 'KML',
    );
  }
  
}