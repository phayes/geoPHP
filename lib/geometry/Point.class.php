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
 * Point : a Point geometry.
 *
 */
class Point extends Geometry
{
  private $position = array(2);
  protected $geom_type = 'Point';
  
  /**
   * Constructor
   *
   * @param float $x The x coordinate (or longitude)
   * @param float $y The y coordinate (or latitude)
   */
  public function __construct($x, $y) {
    if (!is_numeric($x) || !is_numeric($y)) {
      throw new Exception("Bad coordinates: x and y should be numeric");
    }
    
    // Convert to floatval in case they are passed in as a string or integer etc.
    $x = floatval($x);
    $y = floatval($y);
    
    $this->position = array($x, $y);
  }
  
  /**
   * An accessor method which returns the coordinates array
   *
   * @return array The coordinates array
   */
  public function getCoordinates() {
    return $this->position;
  }
  
  /**
   * Returns X coordinate of the point
   *
   * @return integer The X coordinate
   */
  public function x() {
    return $this->position[0];
  }

  /**
   * Returns X coordinate of the point
   *
   * @return integer The X coordinate
   */
  public function y() {
    return $this->position[1];
  }
  
  // A point's centroid is itself
  public function centroid() {
    return $this; 
  }
  
  public function getBBox() {
    return array(
      'maxy' => $this->getY(),
      'miny' => $this->getY(),
      'maxx' => $this->getX(),
      'minx' => $this->getX(),
    );
  }
  
  public function area() {
    return 0;
  }
  
  public function length() {
    return 0;
  }
  
  // The bounadry of a point is itself
  public function boundary() {
    return $this;
  }
  
  public function dimension() {
    return 0;
  }
  
  // Not valid for this geometry type
  public function numGeometries()    { return NULL; }
  public function geometryN($n)      { return NULL; }
  public function startPoint()       { return NULL; }
  public function endPoint()         { return NULL; }
  public function isRing()           { return NULL; }
  public function isClosed()         { return NULL; }
  public function numPoints()        { return NULL; }
  public function pointN($n)         { return NULL; }
  public function exteriorRing()     { return NULL; }
  public function numInteriorRings() { return NULL; }
  public function interiorRingN($n)  { return NULL; }
  public function pointOnSurface()   { return NULL; }

}

