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
  public function __construct(array $geometries = null) {
    parent::__construct($geometries);
  }
  
  /**
   * Returns an array suitable for serialization
   *
   * Overrides the one defined in parent class
   *
   * @return array
   */
  public function getGeoInterface() {
    $geometries = array();
    foreach ($this->components as $geometry) {
      $geometries[] = $geometry->getGeoInterface();
    }
    return array(
      'type' => $this->getGeomType(),
      'geometries' => $geometries
    );
  }

  // Not valid for this geomettry
  public function boundary() { return NULL; }
  public function isSimple() { return NULL; }
}

