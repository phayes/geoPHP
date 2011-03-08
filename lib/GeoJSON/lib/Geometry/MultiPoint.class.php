<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MultiPoint : a MultiPoint geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class MultiPoint extends Collection 
{
  protected $geom_type = 'MultiPoint';
  
  /**
   * Constructor
   *
   * @param array $points The Point array
   */
  public function __construct(array $points) 
  {
    parent::__construct($points);
  }
  
}

