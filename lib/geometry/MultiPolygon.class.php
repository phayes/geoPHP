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
 * MultiPolygon : a MultiPolygon geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class MultiPolygon extends Collection 
{
  protected $geom_type = 'MultiPolygon';
  
  /**
   * Constructor
   *
   * @param array $polygons The Polygon array
   */
  public function __construct(array $polygons) {
    parent::__construct($polygons);
  }
  
}

