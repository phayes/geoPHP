<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Feature class : represents a feature.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version
 */
class Feature
{
  /**
   * The feature id
   */
  private $id = null;

  /**
   * The Geometry object
   */
  private $geometry = null;

  /**
   * The properties array
   */
  private $properties = null;

  /**
   * The bbox
   */
  private $bbox = null;



  /**
   * Constructor
   *
   * @param string $id The feature id
   * @param Geometry $geometry The feature geometry
   * @param array $properties The feature properties
   */
  public function __construct($id=null, Geometry $geometry = null, array $properties = null, array $bbox = null)
  {
    $this->id = $id;
    $this->geometry = $geometry;
    $this->properties = $properties;
    $this->bbox= $bbox;
  }

  /**
   * Set Id
   *
   * @param int $id
   *
   * @return Feature
   */
  public function setId($id)
  {
    $this->id = $id;
    return $this;
  }

  /**
   * Set geometry
   *
   * @param Geometry $geometry
   *
   * @return Feature
   */
  public function setGeometry(Geometry $geometry)
  {
    $this->geometry = $geometry;
    return $this;
  }

  /**
   * Set properties
   *
   * @param array $properties
   *
   * @return Feature
   */
  public function setProperties($properties)
  {
    $this->properties = $properties;
    return $this;
  }

  /**
   * Set property
   *
   * @param string $name A property name
   * @param mixed $value A property value
   *
   * @return Feature
   */
  public function setProperty($name, $value)
  {
    $this->properties[$name] = $value;
    return $this;
  }

  /**
   * Add property
   *
   * @param string $name A property name
   * @param mixed $value A property value
   *
   * @return Feature
   */
  public function addProperty($name, $value)
  {
    return $this->setProperty($name, $value);
  }

  /**
   * Get id
   *
   * @return mixed
   *
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Get geometry
   *
   * @return Geometry
   *
   */
  public function getGeometry()
  {
    return $this->geometry;
  }

  /**
   * Get properties
   *
   * @return array
   */
  public function getProperties()
  {
    return $this->properties;
  }

  /**
   * Get properties
   *
   * @param string $name A property name
   *
   * @return mixed The Property value
   */
  public function getProperty($name)
  {
    if (!array_key_exists($name, $this->properties))
    {
      throw new Exception(sprintf('Feature doesn\'t have "%s" property.', $name));
    }

    return $this->properties[$name];
  }

  /**
   * Returns an array suitable for serialization
   *
   * @return array
   */
  public function getGeoInterface()
  {
    $r = array(
      'type' => 'Feature',
      'id' => $this->id,
      'geometry' => (is_null($this->geometry))?null:$this->geometry->getGeoInterface(),
      'properties' => $this->properties
    );

    if (!is_null($this->bbox))
    {
      $r['bbox'] = $this->bbox;
    }

    return $r;
  }
}

