<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * GeometryCollection : a GeometryCollection geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class GeometryCollection extends Collection 
{
  protected $geom_type = 'GeometryCollection';
  
  /**
   * Constructor
   *
   * @param array $geometries The Geometries array
   */
  public function __construct(array $geometries = null) 
  {
    parent::__construct($geometries);
  }

  /**
   * Returns an array suitable for serialization
   *
   * Overrides the one defined in parent class
   *
   * @return array
   */
  public function getGeoInterface() 
  {
    $geometries = array();
    foreach ($this->components as $geometry) 
    {
      $geometries[] = $geometry->getGeoInterface();
    }
    return array(
      'type' => $this->getGeomType(),
      'geometries' => $geometries
    );
  }
}

