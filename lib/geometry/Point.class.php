<?php

/**
 * Point: The most basic geometry type. All other geometries
 * are built out of Points.
 */
class Point extends Geometry
{
  public $coords = array(2);
  protected $geom_type = 'Point';
  protected $dimention = 2;

  /**
   * Constructor
   *
   * @param numeric $x The x coordinate (or longitude)
   * @param numeric $y The y coordinate (or latitude)
   * @param numeric $z The z coordinate (or altitude) - optional
   */
  public function __construct($x, $y, $z = NULL) {
    // Basic validation on x and y
    if (!is_numeric($x) || !is_numeric($y)) {
      throw new Exception("Cannot construct Point. x and y should be numeric");
    }

    // Check to see if this is a 3D point
    if ($z !== NULL) {
      if (!is_numeric($z)) {
       throw new Exception("Cannot construct Point. z should be numeric");
      }
      $this->dimention = 3;
    }

    // Convert to floatval in case they are passed in as a string or integer etc.
    $x = floatval($x);
    $y = floatval($y);
    $z = floatval($z);

    // Add poitional elements
    if ($this->dimention == 2) {
      $this->coords = array($x, $y);
    }
    if ($this->dimention == 3) {
      $this->coords = array($x, $y, $z);
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
    if ($this->dimention == 3) {
      return $this->coords[2];
    }
    else return NULL;
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

