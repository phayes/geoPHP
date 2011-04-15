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
  public function __construct(array $positions) {
    if (count($positions) > 1)
    {
      parent::__construct($positions);
    }
    else
    {
      throw new Exception("Linestring with less than two points");
    }
  }
  
  // The boundary of a linestring is itself
  public function boundary() {
    return $this;
  }
  
  public function startPoint() {
    return $this->pointN(1);
  }
  
  public function endPoint() {
    $last_n = $this->numPoints();
    return $this->pointN($last_n);
  }
  
  public function isClosed() {
    //@@TODO: Need to complete equal() first;
    #return ($this->startPoint->equal($this->endPoint()));
  }
  
  public function isRing() {
    //@@TODO: need to complete isSimple first
    #return ($this->isClosed() && $this->isSimple());
  }
  
  public function numPoints() {
    return $this->numGeometries();
  }
  
  public function pointN($n) {
    return $this->geometryN($n);
  }
  
  public function dimension() {
    return 1;
  }
  
  public function area() {
    return 0;
  }

}

