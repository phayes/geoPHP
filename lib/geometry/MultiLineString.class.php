<?php
/*
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MultiLineString : a MultiLineString geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class MultiLineString extends Collection 
{
  protected $geom_type = 'MultiLineString';
  
  /**
   * Constructor
   *
   * @param array $linestrings The LineString array
   */
  public function __construct(array $linestrings) {
    parent::__construct($linestrings);
  }
  
  // Length of a MultiLineString is the sum of it's components
  public function length() {
    if ($this->geos()) {
      return $this->geos()->length();
    }
    
    $length = 0;
    foreach ($this->components as $line) {
      $length += $line->length();
    }
    return $length;
  }
  
  // MultiLineString is closed if all it's components are closed
  public function isClosed() {
    foreach ($this->components as $line) {
      if (!$line->isClosed()) {
        return FALSE;
      }
    }
    return TRUE;
  }
  
}

