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
 * LineString : a LineString geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class LineString extends Collection 
{
  protected $geom_type = 'LineString';
  
  /**
   * Constructor
   *
   * @param array $positions The Point array
   */
  public function __construct(array $positions) 
  {
    if (count($positions) > 1)
    {
      parent::__construct($positions);
    }
    else
    {
      throw new Exception("Linestring with less than two points");
    }
  }

  public function intersects($distance) {
    //TODO
  }
  
}

