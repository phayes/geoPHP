<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Polygon : a Polygon geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class Polygon extends Collection 
{
  protected $geom_type = 'Polygon';
  
  /**
   * Constructor
   *
   * The first linestring is the outer ring
   * The subsequent ones are holes
   * All linestrings should be linearrings
   *
   * @param array $linestrings The LineString array
   */
  public function __construct(array $linestrings) 
  {
    // the GeoJSON spec (http://geojson.org/geojson-spec.html) says nothing about linestring count. 
    // What should we do ?
    if (count($linestrings) > 0) 
    {
      parent::__construct($linestrings);
    }
    else
    {
      throw new Exception("Polygon without an exterior ring");
    }
  }
}

