<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
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
  public function __construct(array $polygons) 
  {
    parent::__construct($polygons);
  }
  
}

