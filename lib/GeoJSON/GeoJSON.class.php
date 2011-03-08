<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * GeoJSON class : a geojson reader/writer.
 *
 * This singleton is used to convert GeoJSON strings into PHP objects, and vice-versa
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 */
class GeoJSON
{

  /**
   * Load Classes needed for GeoJSON class
   *
   *
   * Usage:
   *   <?php spl_autoload_register(array('GeoJSON', 'autoload')); ?>
   *
   * @param string $className A class name to load
   */
  static public function autoload($className)
  {

    $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__FILE__)));
    foreach ($i as $file)
    {
      if ($className === basename($file->getFileName(), '.class.php'))
      {
        require_once $file->getPathName();
      }
    }

  }

  /**
   * Serializes an object into a geojson string
   *
   *
   * @param Feature|FeatureCollection $obj The object to serialize
   *
   * @return string The GeoJSON string
   */
  static public function dump($obj)
  {
    if (is_null($obj))
    {
      return null;
    }

    if (!in_array(get_class($obj), array('Feature', 'FeatureCollection')))
    {
      throw new Exception('Input should be a Feature or a FeatureCollection');
    }

    return json_encode($obj->getGeoInterface());
  }

  /**
   * Deserializes a geojson string into an object
   *
   *
   * @param string $string The GeoJSON string
   *
   * @return object The PHP equivalent object
   */
  static public function load($string)
  {
    if (!($object = json_decode($string)))
    {
      throw new Exception('Invalid JSON');
    }
    return self::toInstance($object);
  }

  /**
   * returns a Feature|FeatureCollection instance build from $object through $adapter
   *
   * @param mixed $object The data to load
   * @param GeoJSON_Adapter The adapter through which data will be extracted
   *
   * @return Feature|FeatureCollection A Feature|FeatureCollection instance
   */
  static public function loadFrom($object, GeoJSON_Adapter $adapter)
  {
    if ($adapter->isMultiple($object))
    {
      $result = new FeatureCollection();
      foreach ($adapter->getIterable($object) as $feature)
      {
        $result->addFeature(self::loadFeatureFrom($feature, $adapter));
      }
    }
    else
    {
      $result = self::loadFeatureFrom($object, $adapter);
    }

    return $result;
  }

  /**
   * returns a GeoJSON instance build from $object through $adapter
   *
   * @param mixed $object The data to load
   * @param GeoJSON_Adapter The adapter through which data will be extracted
   *
   * @return GeoJSON A GeoJSON instance
   */
  static protected function loadFeatureFrom($object, GeoJSON_Adapter $adapter)
  {
    $geometry = WKT::load($adapter->getObjectGeometry($object));
    $feature = new Feature(
      $adapter->getObjectId($object),
      $geometry,
      $adapter->getObjectProperties($object),
      $adapter->getObjectBBox($object)
    );

    return $feature;
  }

  /**
   * Converts an stdClass object into a Feature or a FeatureCollection or a Geometry based on its 'type' property
   * Converts an stdClass object into a Feature or a FeatureCollection or a Geometry, based on its 'type' property
   *
   * @param stdClass $obj Object resulting from json decoding a GeoJSON string
   *
   * @return object Object from class: Feature, FeatureCollection or Geometry
   */
  static private function toInstance($obj)
   {
     if (is_null($obj))
     {
       return null;
     }
     if (!isset($obj->type))
     self::checkType($obj);

     switch ($obj->type)
     {
       case 'FeatureCollection':
         $features = array();
         self::checkExists($obj, 'features', false, 'array');
         foreach ($obj->features as $feature)
         {
           $features[] = self::toFeatureInstance($feature, true);
         }
         $instance = new FeatureCollection($features);
         break;
       case 'Feature':
         $instance = self::toFeatureInstance($obj, false);
         break;

       default:
         $instance = self::toGeomInstance($obj);
     }
     return $instance;
   }

  /**
   * Converts an stdClass object into a Feature
   *
   * @param stdClass $obj Object to convert
   * @param boolean $testType Should we test that $obj is really a Feature ?
   * @return object Object from class Feature
   */
  static private function toFeatureInstance($obj, $testType)
  {
    if ($testType)
    {
      self::checkType($obj, 'Feature');
    }
    self::checkExists($obj, 'geometry', true, 'object');
    $geometry = self::toGeomInstance($obj->geometry);
    self::checkExists($obj, 'properties', true, 'object');
    $properties = get_object_vars($obj->properties);
    return new Feature($obj->id, $geometry, $properties);
  }

  /**
   * Converts an stdClass object into a Geometry based on its 'type' property
   *
   * @param stdClass $obj Object resulting from json decoding a GeoJSON string
   * @param boolean $allowGeometryCollection Do we allow $obj to be a GeometryCollection ?
   *
   * @return object Object from class Geometry
   */
  static private function toGeomInstance($obj, $allowGeometryCollection = true)
  {
    if (is_null($obj))
    {
      return null;
    }

    self::checkType($obj);

    switch ($obj->type)
    {
      case 'FeatureCollection':
        $features = array();
        self::checkArray($obj, 'features');
        foreach ($obj->features as $feature)
        {
          $features[] = self::toInstance($feature);
        }
        $instance = new FeatureCollection($features);
        break;

      case 'Feature':
        self::checkExists($obj, 'geometry', true);
        $geometry = self::toInstance($obj->geometry);
        self::checkExists($obj, 'properties', true);
        // TODO : more tests (either array or object ???) see geojson spec
        $properties = (is_object($obj->properties)) ? get_object_vars($obj->properties) : $obj->properties;
        self::checkExists($obj, 'id');
        $instance = new Feature($obj->id, $geometry, $properties);
        break;

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
        foreach ($obj->coordinates as $item)
        {
          $items[] = call_user_func(array('self', 'to'.substr($obj->type, 5)), $item);
        }
        $instance = new $obj->type($items);
        break;

      case 'GeometryCollection':
        if ($allowGeometryCollection)
        {
          self::checkExists($obj, 'geometries', false, 'array');
          $geometries = array();
          foreach ($obj->geometries as $geometry)
          {
            $geometries[] = self::toGeomInstance($geometry, false);
          }
          $instance = new GeometryCollection($geometries);
        }
        else
        {
          throw new Exception("Bad geojson: a GeometryCollection should not contain another GeometryCollection");
        }
        break;

      default:
        throw new Exception("Unsupported object type");
    }
    return $instance;
  }

  /**
   * Checks an object for type
   *
   * @param object $obj A geometry object
   * @param string $typeValue Value expected for 'type' property
   */
  static private function checkType($obj, $typeValue = null)
  {
    if (!is_object($obj) || get_class($obj) != 'stdClass')
    {
      throw new Exception("Bad geojson");
    }

    if (!isset($obj->type))
    {
      throw new Exception("Bad geojson: Missing 'type' property");
    }

    if (!is_null($typeValue) && $obj->type != $typeValue)
    {
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
  static private function checkExists($obj, $property, $allowNull = false, $type = null)
  {
    if (!property_exists($obj, $property))
    {
      throw new Exception("Bad geojson: Missing '$property' property");
    }

    if (is_null($obj->$property))
    {
      if (!$allowNull)
      {
        throw new Exception("Bad geojson: Null value for '$property' property");
      }
    }
    else
    {
      switch ($type)
      {
        case null:
          break;
        case 'array':
          if (!is_array($obj->$property))
          {
            throw new Exception("Bad geojson: Unexpected type for '$property' property");
          }
          break;
        case 'object':
          if (!is_object($obj->$property))
          {
            throw new Exception("Bad geojson: Unexpected type for '$property' property");
          }
          break;
        default:
          throw new Exception("Unexpected error");
      }
    }
  }

  /**
   * Converts an array of coordinates into a Point Feature
   *
   * @param array $coordinates The X/Y coordinates
   *
   * @return Point A Point object
   */
  static private function toPoint(array $coordinates)
  {
    if (count($coordinates) == 2 && isset($coordinates[0]) && isset($coordinates[1]))
    {
      return new Point($coordinates[0], $coordinates[1]);
    }
    throw new Exception("Bad geojson: wrong point coordinates array");
  }

  /**
   * Converts an array of coordinate arrays into a LineString Feature
   *
   * @param array $coordinates The array of coordinates arrays (aka positions)
   * @return LineString A LineString object
   */
  static private function toLineString(array $coordinates)
  {
    $positions = array();
    foreach ($coordinates as $position)
    {
      $positions[] = self::toPoint($position);
    }
    return new LineString($positions);
  }

  /**
   * Converts an array of linestring coordinates into a Polygon Feature
   *
   * @param array $coordinates The linestring coordinates
   * @return Polygon A Polygon object
   */
  static private function toPolygon(array $coordinates)
  {
    $linestrings = array();
    foreach ($coordinates as $linestring) {
      $linestrings[] = self::toLineString($linestring);
    }
    return new Polygon($linestrings);
  }

}


