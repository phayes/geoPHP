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
 * GeoJSON class : a geojson reader/writer.
 * 
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJSON extends GeoAdapter
{
  /**
   * Deserializes a GeoJSON into an object
   *
   * @param mixed $input The GeoJSON string or object
   *
   * @return object Geometry
   */
  public function read($input) {
    if (is_string($input)) {
      $input = json_decode($input);
    }
    if (!is_object($input)) {
      throw new Exception('Invalid JSON');
    }
    return self::toInstance($input);
  }
  
  /**
   * Serializes an object into a geojson string
   *
   *
   * @param Geometry $obj The object to serialize
   *
   * @return string The GeoJSON string
   */
  public function write(Geometry $geometry) {
    if (is_null($geometry)) {
      return null;
    }

    return json_encode($geometry->getGeoInterface());
  }
  
  /**
   * Converts an stdClass object into a Geometry based on its 'type' property
   * Converts an stdClass object into a Geometry, based on its 'type' property
   *
   * @param stdClass $obj Object resulting from json decoding a GeoJSON string
   *
   * @return object Object from class eometry
   */
  static private function toInstance($obj) {
    if (is_null($obj)) {
      return null;
    }
    if (!isset($obj->type)) {
      self::checkType($obj);
    }
    
    if ($obj->type == 'Feature') {
      $instance = self::toGeomInstance($obj->geometry);
    }
    else if ($obj->type == 'FeatureCollection') {
      $geometries = array();
      foreach ($obj->features as $feature) {
        $geometries[] = self::toGeomInstance($feature->geometry);
      }
      // Get a geometryCollection or MultiGeometry out of the the provided geometries
      $instance = geoPHP::geometryReduce($geometries);
    }
    else {
      // It's a geometry
      $instance = self::toGeomInstance($obj);
    }
    
    return $instance;
  }
  
  /**
   * Converts an stdClass object into a Geometry based on its 'type' property
   *
   * @param stdClass $obj Object resulting from json decoding a GeoJSON string
   * @param boolean $allowGeometryCollection Do we allow $obj to be a GeometryCollection ?
   *
   * @return object Object from class Geometry
   */
  static private function toGeomInstance($obj, $allowGeometryCollection = true) {
    if (is_null($obj)) {
      return null;
    }
    
    self::checkType($obj);
    
    switch ($obj->type) {
      case 'Point':
      case 'LineString':
      case 'Polygon':
        self::checkExists($obj, 'coordinates', false, 'array');
        $instance = call_user_func(array('self', 'to'.$obj->type), $obj->coordinates);
        break;

      case 'MultiPoint':
      case 'MultiLineString':
      case 'MultiPolygon':
        self::checkExists($obj, 'coordinates', false, 'array');
        $items = array();
        foreach ($obj->coordinates as $item) {
          $items[] = call_user_func(array('self', 'to'.substr($obj->type, 5)), $item);
        }
        $instance = new $obj->type($items);
        break;

      case 'GeometryCollection':
        if ($allowGeometryCollection) {
          self::checkExists($obj, 'geometries', false, 'array');
          $geometries = array();
          foreach ($obj->geometries as $geometry) {
            $geometries[] = self::toGeomInstance($geometry, false);
          }
          $instance = new GeometryCollection($geometries);
        }
        else {
          throw new Exception("Bad geojson: a GeometryCollection should not contain another GeometryCollection");
        }
        break;

      default:
        throw new Exception("Unsupported object type ".$obj->type);
    }
    return $instance;
  }
  
  /**
   * Checks an object for type
   *
   * @param object $obj A geometry object
   * @param string $typeValue Value expected for 'type' property
   */
  static private function checkType($obj, $typeValue = null) {
    if (!is_object($obj) || get_class($obj) != 'stdClass') {
      throw new Exception("Bad geojson");
    }
    
    if (!isset($obj->type)) {
      throw new Exception("Bad geojson: Missing 'type' property");
    }
    
    if (!is_null($typeValue) && $obj->type != $typeValue) {
      throw new Exception("Bad geojson: Unexpected 'type' value");
    }
  }
  
  /**
   * Checks if a property exists inside an object
   *
   * @param object $obj An object
   * @param string $property The property to check
   * @param boolean $allowNull Whether to allow a null value or not (defaults to false)
   * @param string $type Check also $property type (object, array ...)
   */
  static private function checkExists($obj, $property, $allowNull = false, $type = null) {
    if (!property_exists($obj, $property)) {
      throw new Exception("Bad geojson: Missing '$property' property");
    }

    if (is_null($obj->$property)) {
      if (!$allowNull) {
        throw new Exception("Bad geojson: Null value for '$property' property");
      }
    }
    else {
      switch ($type) {
        case null:
          break;
        case 'array':
          if (!is_array($obj->$property)) {
            throw new Exception("Bad geojson: Unexpected type for '$property' property");
          }
          break;
        case 'object':
          if (!is_object($obj->$property)) {
            throw new Exception("Bad geojson: Unexpected type for '$property' property");
          }
          break;
        default:
          throw new Exception("Unexpected error");
      }
    }
  }
  
  /**
   * Converts an array of coordinates into a Point Geomtery
   *
   * @param array $coordinates The X/Y coordinates
   *
   * @return Point A Point object
   */
  static private function toPoint(array $coordinates) {
    if (count($coordinates) == 2 && isset($coordinates[0]) && isset($coordinates[1])) {
      return new Point($coordinates[0], $coordinates[1]);
    }
    throw new Exception("Bad geojson: wrong point coordinates array");
  }
  
  /**
   * Converts an array of coordinate arrays into a LineString Geometry
   *
   * @param array $coordinates The array of coordinates arrays (aka positions)
   * @return LineString A LineString object
   */
  static private function toLineString(array $coordinates) {
    $positions = array();
    foreach ($coordinates as $position) {
      $positions[] = self::toPoint($position);
    }
    return new LineString($positions);
  }
  
  /**
   * Converts an array of linestring coordinates into a Polygon Geometry
   *
   * @param array $coordinates The linestring coordinates
   * @return Polygon A Polygon object
   */
  static private function toPolygon(array $coordinates) {
    $linestrings = array();
    foreach ($coordinates as $linestring) {
      $linestrings[] = self::toLineString($linestring);
    }
    return new Polygon($linestrings);
  }

}


