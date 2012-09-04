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
include_once("lib/adapters/EWKT.class.php");
include_once("lib/adapters/WKB.class.php");
include_once("lib/adapters/EWKB.class.php");
include_once("lib/adapters/KML.class.php");
include_once("lib/adapters/GPX.class.php");
include_once("lib/adapters/GeoRSS.class.php");
include_once("lib/adapters/GoogleGeocode.class.php");
include_once("lib/adapters/GeoHash.class.php");

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
    return '1.0';
  }

  // geoPHP::load($data, $type, $other_args);
  // if $data is an array, all passed in values will be combined into a single geometry
  static function load() {
    $args = func_get_args();

    $data = array_shift($args);
    $type = array_shift($args);

    $type_map = geoPHP::getAdapterMap();

    // Auto-detect type if needed
    if (!$type) {
      // If the user is trying to load a Geometry from a Geometry... Just pass it back
      if (is_object($data)) {
        if ($data instanceOf Geometry) return $data;
      }
      $format = explode(':', geoPHP::detectFormat($data));
      $type = array_shift($format);
      $args = $format;
    }

    $processor_type = $type_map[$type];

    if (!$processor_type) {
      throw new exception('geoPHP could not find an adapter of type '.htmlentities($type));
      exit;
    }

    $processor = new $processor_type();

    // Data is not an array, just pass it normally
    if (!is_array($data)) {
      $result = call_user_func_array(array($processor, "read"), array_merge(array($data), $args));
    }
    // Data is an array, combine all passed in items into a single geomtetry
    else {
      $geoms = array();
      foreach ($data as $item) {
        $geoms[] = call_user_func_array(array($processor, "read"), array_merge(array($item), $args));
      }
      $result = geoPHP::geometryReduce($geoms);
    }

    return $result;
  }

  static function getAdapterMap() {
    return array (
      'wkt' =>  'WKT',
      'ewkt' => 'EWKT',
      'wkb' =>  'WKB',
      'ewkb' => 'EWKB',
      'json' => 'GeoJSON',
      'kml' =>  'KML',
      'gpx' =>  'GPX',
      'georss' => 'GeoRSS',
      'google_geocode' => 'GoogleGeocode',
      'geohash' => 'GeoHash',
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
    $geometry = geoPHP::load($wkb, 'wkb', TRUE);
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
      if (count($geometries) == 1) {
        return $geometries[0];
      }
      else {
        $class = 'Multi'.$geom_types[0];
        return new $class($geometries);
      }
    }
    else {
      return new GeometryCollection($geometries);
    }
  }

  // Detect a format given a value. This function is meant to be SPEEDY.
  // It could make a mistake in XML detection if you are mixing or using namespaces in weird ways (ie, KML inside an RSS feed)
  static function detectFormat(&$input) {
    $mem = fopen('php://memory', 'r+');
    fwrite($mem, $input, 8); // Write 8 bytes - we can detect the vast majority of formats in the first 8 bytes
    fseek($mem, 0);

    $first_byte = array_pop(unpack("c", fread($mem, 1)));

    // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
    if ($first_byte == 1) {
      $wkb_info = unpack("ctype/cz/cm/cs", fread($mem, 4));
      if ($wkb_info['s']) return 'ewkb';
      else return 'wkb';
    }

    // Detect HEX encoded WKB or EWKB -- first byte is 43 (hex-encoded first-byte = 1, second-byte = ???)
    // @@TODO: This needs to be refined
    if ($first_byte == 48) {
      return 'ewkb:1';
    }

    // Detect GeoJSON - first char starts with {
    if ($first_byte == 123) {
      return 'json';
    }

    // Detect EWKT - first char is S
    if ($first_byte == 83) {
      return 'ewkt';
    }

    // Detect WKT - first char starts with P (80), L (76), M (77), or G (71)
    $wkt_chars = array(80, 76, 77, 71);
    if (in_array($first_byte, $wkt_chars)) {
      return 'wkt';
    }

    // Detect XML -- first char is <
    if ($first_byte == 60) {
      // grab the first 256 characters
      $string = substr($input, 0, 256);
      if (strpos($string, '<kml') !== FALSE)     return 'kml';
      if (strpos($string, '<gpx') !== FALSE)     return 'gpx';
      if (strpos($string, '<georss') !== FALSE)  return 'georss';
      if (strpos($string, '<rss') !== FALSE)     return 'georss';
      if (strpos($string, '<feed') !== FALSE)    return 'georss';
    }

    // We need an 8 byte string for geohash and unpacked WKB / WKT
    fseek($mem, 0);
    $string = trim(fread($mem, 8));

    // Detect geohash - geohash ONLY contains lowercase chars and numerics
    preg_match('/[a-z0-9]+/', $string, $matches);
    if ($matches[0] == $string) {
      return 'geohash';
    }

    // What do you get when you cross an elephant with a rhino?
    // http://youtu.be/RCBn5J83Poc
    return FALSE;
  }

}
