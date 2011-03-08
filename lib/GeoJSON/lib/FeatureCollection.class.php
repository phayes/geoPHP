<?php

/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * FeatureCollection class : represents a collection of features.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
 
class FeatureCollection implements Iterator
{
  private $features = null;

  /**
   * Constructor
   *
   * @param array $features The features used to build the collection
   */
  public function __construct(array $features = null)
  {
    $this->features = $features;
  }

  /**
   * Add a feature to collection
   */
  public function addFeature(Feature $f)
  {
    $this->features[] = $f;
  }

  /**
   * Returns an array suitable for serialization
   *
   * @return array
   */
  public function getGeoInterface()
  {
    $features = array();

    if (is_array($this->features))
    {
      foreach ($this->features as $feature) 
      {
        $features[] = $feature->getGeoInterface();
      }
    }

    return array(
      'type' => 'FeatureCollection',
      'features' => $features
    );
  }

  /**
   * Shortcut to dump geometry as GeoJSON
   *
   * @return string The GeoJSON representation of the geometry
   */
  public function __toString()
  {
    return $this->toGeoJSON();
  }

  /**
   * Dumps Geometry as GeoJSON
   *
   * @return string The GeoJSON representation of the geometry
   */
  public function toGeoJSON()
  {
    return json_encode($this->getGeoInterface());
  }
  # Iterator Interface functions

  public function rewind()
  {
    reset($this->features);
  }

  public function current()
  {
    return current($this->features);
  }

  public function key()
  {
    return key($this->features);
  }

  public function next()
  {
    return next($this->features);
  }

  public function valid()
  {
    return $this->current() !== false;
  }
  
}

