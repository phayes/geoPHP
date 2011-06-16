<?php
/*
 * (c) Camptocamp <info@camptocamp.com>
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/WKT encoder/decoder
 *
 * Mainly inspired/adapted from OpenLayers( http://www.openlayers.org ) 
 *   Openlayers/format/WKT.js
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 */
class WKT extends GeoAdapter
{

  private $regExes = array(
    'typeStr'               => '/^\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/',
    'spaces'                => '/\s+/',
    'parenComma'            => '/\)\s*,\s*\(/',
    'doubleParenComma'      => '/\)\s*\)\s*,\s*\(\s*\(/',
    'trimParens'            => '/^\s*\(?(.*?)\)?\s*$/'
  );

  const POINT               = 'point';
  const MULTIPOINT          = 'multipoint';
  const LINESTRING          = 'linestring';
  const MULTILINESTRING     = 'multilinestring';
  const POLYGON             = 'polygon';
  const MULTIPOLYGON        = 'multipolygon';
  const GEOMETRYCOLLECTION  = 'geometrycollection';

  /**
   * Read WKT string into geometry objects
   *
   * @param string $WKT A WKT string
   *
   * @return Geometry|GeometryCollection
   */
  public function read($wkt) {
    $wkt = strval($wkt);
    
    // If geos is installed, then we take a shortcut and let it parse the WKT
    if (geoPHP::geosInstalled()) {
      $reader = new GEOSWKTReader();
      return geoPHP::geosToGeometry($reader->read($wkt));
    }
    
    $matches = array();
    if (!preg_match($this->regExes['typeStr'], $wkt, $matches)) {
      return null;
    }
    
    return $this->parse(strtolower($matches[1]), $matches[2]);
  }
  
  /**
   * Serialize geometries into a WKT string.
   *
   * @param Geometry $geometry
   *
   * @return string The WKT string representation of the input geometries
   */
  public function write(Geometry $geometry) {
    // If geos is installed, then we take a shortcut and let it write the WKT
    if (geoPHP::geosInstalled()) {
      $writer = new GEOSWKTWriter();
      return $writer->write($geometry->geos());
    }
    
    $type = strtolower($geometry->geometryType());
    
    if (is_null($data = $this->extract($geometry))) {
      return null;
    }
    
    return strtoupper($type).' ('.$data.')';
  }

  /**
   * Parse WKT string into geometry objects
   *
   * @param string $WKT A WKT string
   *
   * @return Geometry|GeometryCollection
   */
  public function parse($type, $str) {
    $matches = array();
    $components = array();

    switch ($type) {
      case self::POINT:
        $coords = $this->pregExplode('spaces', $str);
        return new Point(floatval($coords[0]), floatval($coords[1]));

      case self::MULTIPOINT:
        $points = $this->pregExplode('parenComma', $str);
        foreach ($points as $p) {
          $point = $this->trimParens( $p );
          $components[] = $this->parse(self::POINT, $point);
        }
        return new MultiPoint($components);

      case self::LINESTRING:
        foreach (explode(',', trim($str)) as $point) {
          $components[] = $this->parse(self::POINT, $point);
        }
        return new LineString($components);

      case self::MULTILINESTRING:
        $lines = $this->pregExplode('parenComma', $str);
        foreach ($lines as $l) {
          $line = $this->trimParens( $l );
          $components[] = $this->parse(self::LINESTRING, $line);
        }
        return new MultiLineString($components);

      case self::POLYGON:
        $rings= $this->pregExplode('parenComma', $str);
        foreach ($rings as $r) {
          $ring = $this->trimParens( $r );
          $linestring = $this->parse(self::LINESTRING, $ring);
          $components[] = new LineString($linestring->getComponents());
        }
        return new Polygon($components);

      case self::MULTIPOLYGON:
        $polygons = $this->pregExplode('doubleParenComma', $str);
        foreach ($polygons as $p) {
          $polygon = $this->trimParens( $p );
          $components[] = $this->parse(self::POLYGON, $polygon);
        }
        return new MultiPolygon($components);

      case self::GEOMETRYCOLLECTION:
        $str = preg_replace('/,\s*([A-Za-z])/', '|$1', $str);
        $wktArray = explode('|', trim($str));
        foreach ($wktArray as $wkt) {
          $components[] = $this->read($wkt);
        }
        return new GeometryCollection($components);

      default:
        return null;
    }
  }
  
  /**
   * Trim the parenthesis 
   *
   */
  protected function trimParens($str) {
   $open_parent = stripos( $str, '(' );
   $open_parent = ($open_parent!==false)?$open_parent+1:0;
   $close_parent = strripos( $str, ')' );
   $close_parent = ($close_parent!==false)?$close_parent:strlen($str);
   return substr( $str, $open_parent, $close_parent);
  }
  
  /**
   * Split string according to first match of passed regEx index of $regExes
   *
   */
  protected function pregExplode($regEx, $str) {
    $matches = array();
    preg_match($this->regExes[$regEx], $str, $matches);
    return empty($matches)?array(trim($str)):explode($matches[0], trim($str));
  }
  
  /**
   * Extract geometry to a WKT string
   *
   * @param Geometry $geometry A Geometry object
   *
   * @return strin
   */
  public function extract(Geometry $geometry) {
    $array = array();
    switch (strtolower(get_class($geometry))) {
      case self::POINT:
        return $geometry->getX().' '.$geometry->getY();
      case self::LINESTRING:
        foreach ($geometry as $geom) {
          $array[] = $this->extract($geom);
        }
        return implode(', ', $array);
      case self::MULTIPOINT:
      case self::MULTILINESTRING:
      case self::POLYGON:
      case self::MULTIPOLYGON:
        foreach ($geometry as $geom) {
          $array[] = '('.$this->extract($geom).')';
        }
        return implode(', ', $array);
      case self::GEOMETRYCOLLECTION:
        foreach ($geometry as $geom) {
          $array[] = strtoupper(get_class($geom)).' ('.$this->extract($geom).')';
        }
        return implode(', ', $array);
      default:
        return null;
    }
  }
  
  /**
   * Loads a WKT string into a Geometry Object
   *
   * @param string $WKT
   *
   * @return  Geometry
   */
  static public function load($WKT) {
    $instance = new self;
    return $instance->read($WKT);
  }

}
