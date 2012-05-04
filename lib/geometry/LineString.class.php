<?php
/**
 * LineString. A collection of Points representing a line.
 * A line can have more than one segment.   
 */
class LineString extends Collection
{
  protected $geom_type = 'LineString';
  
  /**
   * Constructor
   *
   * @param array $points An array of at least two points with
   * which to build the LineString
   */
  public function __construct($points = array()) {
    if (count($points) == 1) {
      throw new Exception("Cannot construct a LineString with a single point");
    }
    
    // Call the Collection constructor to build the LineString
    parent::__construct($points);
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
    return ($this->startPoint()->equals($this->endPoint()));
  }
  
  public function isRing() {
    return ($this->isClosed() && $this->isSimple());
  }
  
  public function numPoints() {
    return $this->numGeometries();
  }
  
  public function pointN($n) {
    return $this->geometryN($n);
  }
  
  public function dimension() {
    if ($this->isEmpty()) return 0;
    return 1;
  }
  
  public function area() {
    return 0;
  }
  
  public function explode() {
    $parts = array();
    $points = $this->getPoints();
    
    foreach ($points as $i => $point) {
      if (isset($points[$i+1])) {
        $parts[] = new LineString(array($point, $points[$i+1]));
      }
    }
    return $parts;
  }
  
  public function isSimple() {
    if ($this->geos()) {
      return $this->geos()->isSimple();
    }
    
    $segments = $this->explode();
    
    foreach ($segments as $i => $segment) {
      foreach ($segments as $j => $check_segment) {
        if ($i != $j) {
          if ($segment->lineSegmentIntersect($check_segment)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }
  
  // Utility function to check if any line sigments intersect
  // Derived from http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
  public function lineSegmentIntersect($segment) {  
    $p0_x = $this->startPoint()->x();
    $p0_y = $this->startPoint()->y();
    $p1_x = $this->endPoint()->x();
    $p1_y = $this->endPoint()->y();
    $p2_x = $segment->startPoint()->x();
    $p2_y = $segment->startPoint()->y();
    $p3_x = $segment->endPoint()->x();
    $p3_y = $segment->endPoint()->y();
              
    $s1_x = $p1_x - $p0_x;     $s1_y = $p1_y - $p0_y;
    $s2_x = $p3_x - $p2_x;     $s2_y = $p3_y - $p2_y;
    
    $fps = (-$s2_x * $s1_y) + ($s1_x * $s2_y);
    $fpt = (-$s2_x * $s1_y) + ($s1_x * $s2_y);
    
    if ($fps == 0 || $fpt == 0) {
      return FALSE;
    }
    
    $s = (-$s1_y * ($p0_x - $p2_x) + $s1_x * ($p0_y - $p2_y)) / $fps;
    $t = ( $s2_x * ($p0_y - $p2_y) - $s2_y * ($p0_x - $p2_x)) / $fpt;

    if ($s > 0 && $s < 1 && $t > 0 && $t < 1) {
      // Collision detected
      return TRUE;
    }
    return FALSE;
  }
}

