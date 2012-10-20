<?php

/**
 * Point: The most basic geometry type. All other geometries
 * are built out of Points.
 */
class Point extends Geometry 
{
  protected $geom_type = 'Point';
  protected $dimension = 2;

  public $coords = array(2);
  public $m;

  /**
   * Constructor
   *
   * @param numeric $x The x coordinate (or longitude)
   * @param numeric $y The y coordinate (or latitude)
   * @param numeric $z The z coordinate (or altitude) - optional
   * @param numeric $m measure - optional
   */
  public function __construct($x, $y, $z = NULL, $m = NULL) {
    // Basic validation on x and y
    if (!is_numeric($x) || !is_numeric($y)) {
      throw new Exception("Cannot construct Point. x and y should be numeric");
    }

    // Check to see if this is a 3D point
    if ($z !== NULL) {
      if (!is_numeric($z)) {
       throw new Exception("Cannot construct Point. z should be numeric");
      }
      $this->dimension++;
    }
    
    // Check to see if this is a measure
    if ($m !== NULL) {
    	if (!is_numeric($m)) {    		
    		throw new Exception("Cannot construct Point. m should be numeric");
    	}    	
    	$this->setMeasured(TRUE);
      $this->dimension++;
    }

    // Convert to floatval in case they are passed in as a string or integer etc.
    $x = floatval($x);
    $y = floatval($y);
    $z = floatval($z);
    $m = floatval($m);

    // Set position
    if (!$z) {
      $this->coords = array($x, $y);
    }
    else {
      $this->coords = array($x, $y, $z);
    }
    if ($m) {
      $this->m = $m;
    }
  }

  /**
   * Get X (longitude) coordinate
   *
   * @return float The X coordinate
   */
  public function x() {
    return $this->coords[0];
  }

  /**
   * Returns Y (latitude) coordinate
   *
   * @return float The Y coordinate
   */
  public function y() {
    return $this->coords[1];
  }

  /**
   * Returns Z (altitude) coordinate
   *
   * @return float The Z coordinate or NULL is not a 3D point
   */
  public function z() {
    if ( $this->hasZ() ) return $this->coords[2];
  }
  
  /**
   * Return a measured value
   *
   * @return a measured value
   */
  public function m() {
  	return $this->m;
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

  public function asArray($assoc = FALSE) {
    return $this->coords;
  }

  public function area() {
    return 0;
  }

  public function length() {
    return 0;
  }

  public function length3D() {
    return 0;
  }

  public function greatCircleLength() {
    return 0;
  }

  public function haversineLength() {
    return 0;
  }

  // The boundary of a point is itself
  public function boundary() {
    return $this;
  }

  public function dimension() {
    return 0;
  }

  public function isEmpty() {
    return FALSE;
  }

  public function numPoints() {
    return 1;
  }

  public function getPoints() {
    return array($this);
  }

  public function equals($geometry) {
    return ($this->x() == $geometry->x() && $this->y() == $geometry->y());
  }

  public function isSimple() {
    return TRUE;
  }

  public function flatten() {
    if ( $this->hasZ() ) return new Point($this->x(), $this->y());
    return $this;
  }

  public function distance(Geometry $geometry) {   
    if ($this->geos()) {
      return $this->geos()->distance($geometry->geos());
    }

    if ($geometry->isEmpty()) return NULL;

    if ($geometry->geometryType() == 'Point') {
      if ($this->equals($geometry)) return 0;
      return sqrt(pow(($this->x() - $geometry->x()), 2) + pow(($this->y() - $geometry->y()), 2));
    }
    if ($geometry->geometryType() == 'MultiPoint' || $geometry->geometryType() == 'GeometryCollection') {
      $distance = NULL;
      foreach ($geometry->getComponents() as $component) {
        $check_distance = $this->distance($component);
        if ($check_distance === 0) return 0;
        if ($check_distance === NULL) return NULL;
        if ($distance === NULL) $distance = $check_distance;
        if ($check_distance < $distance) $distance = $check_distance;
      }
      return $distance;
    }
    else {
      // For LineString, Polygons, MultiLineString and MultiPolygon. the nearest point might be a vertex,
      // but it could also be somewhere along a line-segment that makes up the geometry (between vertices).
      // Here we brute force check all line segments that make up these geometries
      $distance = NULL;
      $segments = $geometry->explode();
      foreach ($segments as $seg) {
        // As per http://stackoverflow.com/questions/849211/shortest-distance-between-a-point-and-a-line-segment
        // and http://paulbourke.net/geometry/pointline/

        $x1 = $seg->pointN(1)->x();
        $y1 = $seg->pointN(1)->y();
        $x2 = $seg->pointN(2)->x();
        $y2 = $seg->pointN(2)->y();
        $x3 = $this->x();
        $y3 = $this->y();

        $px = $x2 - $x1;
        $py = $y2 - $y1;

        $d = ($px*$px) + ($py*$py);

        if ($d == 0) {
          // Line-sigment's endpoints are identical. This is merely a point masquerading as a line-sigment.
          $check_distance = $this->distance($seg->pointN(1));
        }
        else {
          $u =  ((($x3 - $x1) * $px) + (($y3 - $y1) * $py)) / $d;

          if ($u > 1) $u = 1;
          if ($u < 0) $u = 0;

          $x = $x1 + ($u * $px);
          $y = $y1 + ($u * $py);

          $dx = $x - $x3;
          $dy = $y - $y3;

          $check_distance = sqrt(($dx * $dx) + ($dy * $dy));
        }

        if ($distance === NULL) $distance = $check_distance;
        if ($check_distance < $distance) $distance = $check_distance;
      }
      return $distance;
    }
  }

  // Not valid for this geometry type
  public function numGeometries()    { return NULL; }
  public function geometryN($n)      { return NULL; }
  public function startPoint()       { return NULL; }
  public function endPoint()         { return NULL; }
  public function isRing()           { return NULL; }
  public function isClosed()         { return NULL; }
  public function pointN($n)         { return NULL; }
  public function exteriorRing()     { return NULL; }
  public function numInteriorRings() { return NULL; }
  public function interiorRingN($n)  { return NULL; }
  public function pointOnSurface()   { return NULL; }
  public function explode()          { return NULL; }
}

