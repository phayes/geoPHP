<?php
/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Adapters
include_once("lib/adapters/GeoAdapter.class.php"); // Abtract class
include_once("lib/adapters/GeoJSON.class.php");
include_once("lib/adapters/WKT.class.php");
include_once("lib/adapters/WKB.class.php");
include_once("lib/adapters/KML.class.php");
include_once("lib/adapters/GPX.class.php");
include_once("lib/adapters/GeoRSS.class.php");
include_once("lib/adapters/GoogleGeocode.class.php");

// Geometries
include_once("lib/geometry/Geometry.class.php"); // Abtract class
include_once("lib/geometry/Point.class.php");
include_once("lib/geometry/Collection.class.php"); // Abtract class
include_once("lib/geometry/LineString.class.php");
include_once("lib/geometry/MultiPoint.class.php");
include_once("lib/geometry/Polygon.class.php");
include_once("lib/geometry/MultiLineString.class.php");
include_once("lib/geometry/MultiPolygon.class.php");
include_once("lib/geometry/GeometryCollection.class.php");

class geoPHP
{
  
  static function version() {
    return '0.6';
  }
  
  // geoPHP::load($data, $type, $other_args);
  // if $data is an array, all passed in values will be combined into a single geometry
  static function load() {
    $args = func_get_args();
    
    $data = array_shift($args);
    $type = array_shift($args);
    
    $type_map = geoPHP::getAdapterMap();
    
    $processor_type = $type_map[$type];
    $processor = new $processor_type();
    
    // Data is not an array, just pass it normally
    if (!is_array($data)) {
      $result = call_user_func_array(array($processor, "read"), array_merge(array($data), $args));
    }
    // Data is an array, combine all passed in items into a single geomtetry
    else {
      $geoms = array();
      foreach ($data as $item) {
        $geoms[] = call_user_func_array(array($processor, "read"), array_merge(array($data), $args));
      }
      $result = geoPHP::geometryReduce($geoms);
    }
    
    return $result;
  }
  
  static function getAdapterMap() {
    return array (
      'wkt' => 'WKT',
      'wkb' => 'WKB',
      'json' => 'GeoJSON',
      'kml' => 'KML',
      'gpx' => 'GPX',
      'georss' => 'GeoRSS',
      'google_geocode' => 'GoogleGeocode',
    );
  }
  
  static function geometryList() {
    return array(
      'point' => 'Point',
      'linestring' => 'LineString',
      'polygon' => 'Polygon',
      'multipoint' => 'MultiPoint',
      'multilinestring' => 'MultiLineString',
      'multipolygon' => 'MultiPolygon',
      'geometrycollection' => 'GeometryCollection',
    );
  }
  
  static function geosInstalled($force = NULL) {
    static $geos_installed = NULL;
    if ($force !== NULL) $geos_installed = $force;
    if ($geos_installed !== NULL) {
      return $geos_installed; 
    }
    $geos_installed = class_exists('GEOSGeometry');
    return $geos_installed;
  }
  
  static function geosToGeometry($geos) {
    if (!geoPHP::geosInstalled()) {
      return NULL;
    }
    $wkb_writer = new GEOSWKBWriter();
    $wkb = $wkb_writer->writeHEX($geos);
    $geometry = geoPHP::load($wkb,'wkb',TRUE);
    //@@TODO: We no longer need to check this once we have Empty geometries
    if ($geometry) {
      $geometry->setGeos($geos);
      return $geometry;
    }
  }
  
  // Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
  // For example a GeometryCollection of only points will become a MultiPoint
  // A multi-point containing a single point will return a point.
  // An array of geometries can be passed and they will be compiled into a single geometry
  static function geometryReduce($geometry) {
    // If it's an array of one, then just parse the one
    if (is_array($geometry)) {
      if (count($geometry) == 1) return geoPHP::geometryReduce($geometry[0]);
    }
    
    // If the geometry cannot even theoretically be reduced more, then pass it back
    if (gettype($geometry) == 'object') {
      $passbacks = array('Point','LineString','Polygon');
      if (in_array($geometry->geometryType(),$passbacks)) {
        return $geometry;
      }
    }
    
    // If it is a mutlti-geometry, check to see if it just has one member
    // If it does, then pass the member, if not, then just pass back the geometry
    if (gettype($geometry) == 'object') {
      $simple_collections = array('MultiPoint','MultiLineString','MultiPolygon');
      if (in_array(get_class($geometry),$passbacks)) {
        $components = $geometry->getComponents();
        if (count($components) == 1) {
          return $components[0];
        }
        else {
          return $geometry;
        }
      }
    }
    
    // So now we either have an array of geometries, a GeometryCollection, or an array of GeometryCollections    
    if (!is_array($geometry)) {
      $geometry = array($geometry);
    }
    
    $geometries = array();
    $geom_types = array();
    
    $collections = array('MultiPoint','MultiLineString','MultiPolygon','GeometryCollection');
    
    foreach ($geometry as $item) {
      if (in_array(get_class($item), $collections)) {
        foreach ($item->getComponents() as $component) {
          $geometries[] = $component;
          $geom_types[] = $component->geometryType();
        }
      }
      else {
        $geometries[] = $item;
        $geom_types[] = $item->geometryType();
      }
    }
    
    $geom_types = array_unique($geom_types);
    
    if (count($geom_types) == 1) {
      $class = 'Multi'.$geom_types[0];
      return new $class($geometries);
    }
    else {
      return new GeometryCollection($geometries);
    }
  }
}
