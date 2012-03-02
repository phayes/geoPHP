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
  public function __construct(array $points) {
    if (count($points) < 2) {
      throw new Exception("Cannot construct LineString with less than two points");
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

