<?php

namespace Phayes\GeoPHP;

use Phayes\GeoPHP\Adapters\GeoAdapter;
use Phayes\GeoPHP\Adapters\WKT;
use Phayes\GeoPHP\Adapters\GeoJSON;
use Phayes\GeoPHP\Geometry\Geometry;
use Phayes\GeoPHP\Geometry\GeometryCollection;
use Phayes\GeoPHP\Geometry\Point;
use Phayes\GeoPHP\Geometry\Polygon;
use Phayes\GeoPHP\Geometry\LineString;
use Phayes\GeoPHP\Geometry\MultiPoint;
use Phayes\GeoPHP\Geometry\MultiPolygon;
use Phayes\GeoPHP\Geometry\MultiLineString;

class GeoPHP
{

  public static function version()
  {
    return '1.2';
  }

  // geoPHP::load($data, $type, $other_args);
  // if $data is an array, all passed in values will be combined into a single geometry
  public static function load()
  {
    $args = func_get_args();

    $data = array_shift($args);
    $type = array_shift($args);

    $type_map = self::getAdapterMap();

    // Auto-detect type if needed
    if (!$type) {
      // If the user is trying to load a Geometry from a Geometry... Just pass it back
      if (is_object($data)) {
        if ($data instanceOf Geometry) return $data;
      }

      $detected = self::detectFormat($data);
      if (!$detected) {
        return false;
      }

      $format = explode(':', $detected);
      $type = array_shift($format);
      $args = $format;
    }

    // 15.05.2016 rd1988
    // Fix for composer autoloading
    // We have to put full namespace if we are calling our class dynamically
    $processor_type = "Phayes\\GeoPHP\\Adapters\\".$type_map[$type];

    if (!$processor_type) {
      throw new \Exception('geoPHP could not find an adapter of type '.htmlentities($type));
    }

    $processor = new $processor_type();

    // Data is not an array, just pass it normally
    if (!is_array($data)) {
      $result = call_user_func_array(array($processor, "read"), array_merge(array($data), $args));
    } else {
      // Data is an array, combine all passed in items into a single geometry
      $geoms = array();

      foreach ($data as $item) {
        $geoms[] = call_user_func_array(array($processor, "read"), array_merge(array($item), $args));
      }

      $result = self::geometryReduce($geoms);
    }

    return $result;
  }

  public static function getAdapterMap()
  {
    return [
      'wkt' =>  'WKT',
      'ewkt' => 'EWKT',
      'wkb' =>  'WKB',
      'ewkb' => 'EWKB',
      'json' => 'GeoJSON',
      'geojson' => 'GeoJSON',
      'kml' =>  'KML',
      'gpx' =>  'GPX',
      'georss' => 'GeoRSS',
      'google_geocode' => 'GoogleGeocode',
      'geohash' => 'GeoHash',
    ];
  }

  public static function geometryList()
  {
    return [
      'point' => 'Point',
      'linestring' => 'LineString',
      'polygon' => 'Polygon',
      'multipoint' => 'MultiPoint',
      'multilinestring' => 'MultiLineString',
      'multipolygon' => 'MultiPolygon',
      'geometrycollection' => 'GeometryCollection',
    ];
  }

  public static function geosInstalled($force = null)
  {
    static $geos_installed = null;

    if ($force !== null) $geos_installed = $force;
    if ($geos_installed !== null) {
      return $geos_installed;
    }

    $geos_installed = class_exists('GEOSGeometry');

    return $geos_installed;
  }

  public static function geosToGeometry()
  {
    if (!self::geosInstalled()) {
      return null;
    }

    $wkb_writer = new GEOSWKBWriter();
    $wkb = $wkb_writer->writeHEX($geos);

    $geometry = self::load($wkb, 'wkb', TRUE);

    if ($geometry) {
      $geometry->setGeos($geos);
      return $geometry;
    }
  }

  // Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
  // For example a GeometryCollection of only points will become a MultiPoint
  // A multi-point containing a single point will return a point.
  // An array of geometries can be passed and they will be compiled into a single geometry
  public static function geometryReduce($geometry)
  {
    // If it's an array of one, then just parse the one
    if (is_array($geometry)) {
      if (empty($geometry)) return false;
      if (count($geometry) == 1) return self::geometryReduce(array_shift($geometry));
    }

    // If the geometry cannot even theoretically be reduced more, then pass it back
    if (gettype($geometry) == 'object') {
      $passbacks = ['Point','LineString','Polygon'];
      if (in_array($geometry->geometryType(),$passbacks)) {
        return $geometry;
      }
    }

    // If it is a mutlti-geometry, check to see if it just has one member
    // If it does, then pass the member, if not, then just pass back the geometry
    if (gettype($geometry) == 'object') {
      $simple_collections = ['MultiPoint','MultiLineString','MultiPolygon'];
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
      $geometry = [$geometry];
    }

    $geometries = [];
    $geom_types = [];
    $collections = [
      'MultiPoint',
      'MultiLineString',
      'MultiPolygon',
      'GeometryCollection'
    ];

    foreach ($geometry as $item) {
      if ($item) {
        if (in_array(get_class($item), $collections)) {
          foreach ($item->getComponents() as $component) {
            $geometries[] = $component;
            $geom_types[] = $component->geometryType();
          }
        } else {
          $geometries[] = $item;
          $geom_types[] = $item->geometryType();
        }
      }
    }

    $geom_types = array_unique($geom_types);

    if (empty($geom_types)) {
      return false;
    }


    if (count($geom_types) == 1) {

      if (count($geometries) == 1) {
        return $geometries[0];
      } else {
        $class = 'Multi'.$geom_types[0];
        return new $class($geometries);
      }

    } else {
      return new GeometryCollection($geometries);
    }
  }

  // Detect a format given a value. This function is meant to be SPEEDY.
  // It could make a mistake in XML detection if you are mixing or using namespaces in weird ways (ie, KML inside an RSS feed)
  public static function detectFormat(&$input)
  {
    $mem = fopen('php://memory', 'r+');
    fwrite($mem, $input, 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
    fseek($mem, 0);

    $bytes = unpack("c*", fread($mem, 11));

    // If bytes is empty, then we were passed empty input
    if (empty($bytes)) {
      return false;
    }

    // First char is a tab, space or carriage-return. trim it and try again
    if ($bytes[1] == 9 || $bytes[1] == 10 || $bytes[1] == 32) {
      $ltinput = ltrim($input);
      return self::detectFormat($ltinput);
    }

    // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
    if ($bytes[1] == 1) {
      // If SRID byte is TRUE (1), it's EWKB
      if ($bytes[5]) {
        return 'ewkb';
      } else {
        return 'wkb';
      }
    }

    // Detect HEX encoded WKB or EWKB (PostGIS format) -- first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
    if ($bytes[1] == 48 && $bytes[2] == 49) {
      // The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
      // This differentiates it from a geohash, which is always shorter than 18 characters.
      if (strlen($input) >= 18) {
        //@@TODO: Differentiate between EWKB and WKB -- check hex-char 10 or 11 (SRID bool indicator at encoded byte 5)
        return 'ewkb:1';
      }
    }

    // Detect GeoJSON - first char starts with {
    if ($bytes[1] == 123) {
      return 'json';
    }

    // Detect EWKT - first char is S
    if ($bytes[1] == 83) {
      return 'ewkt';
    }

    // Detect WKT - first char starts with P (80), L (76), M (77), or G (71)
    $wkt_chars = [80, 76, 77, 71];
    if (in_array($bytes[1], $wkt_chars)) {
      return 'wkt';
    }

    // Detect XML -- first char is <
    if ($bytes[1] == 60) {
      // grab the first 256 characters
      $string = substr($input, 0, 256);
      if (strpos($string, '<kml') !== false)        return 'kml';
      if (strpos($string, '<coordinate') !== false) return 'kml';
      if (strpos($string, '<gpx') !== false)        return 'gpx';
      if (strpos($string, '<georss') !== false)     return 'georss';
      if (strpos($string, '<rss') !== false)        return 'georss';
      if (strpos($string, '<feed') !== false)       return 'georss';
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
    return false;
  }
}
