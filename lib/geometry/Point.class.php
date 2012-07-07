<?php

/**
 * Point: The most basic geometry type. All other geometries
 * are built out of Points.
 */
class Point extends Geometry
{
  public $coords = array(2);
  public $elevation = 0;
  protected $geom_type = 'Point';

  /**
   * Constructor
   *
   * @param numeric $x The x coordinate (or longitude)
   * @param numeric $y The y coordinate (or latitude)
   * @param numeric $z The z (or elevation) - optional
   */
  public function __construct($x, $y, $z = 0) {
    // Basic validation on x and y
    if (!is_numeric($x) || !is_numeric($y) || !is_numeric($z)) {
      throw new Exception("Cannot construct Point. x, y and z should be numeric");
    }

    // Convert to floatval in case they are passed in as a string or integer etc.
    $x = floatval($x);
    $y = floatval($y);
    $z = floatval($z);

    $this->coords = array($x, $y);
    $this->elevation = $z;
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
   * Returns Z (altitude) elevation
   *
   * @return float The Z elevation or zero if it's not a 3D point
   */
  public function z() {
      return $this->elevation;
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

  // The bounadry of a point is itself
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
    return (
      $this->x() == $geometry->x() &&
      $this->y() == $geometry->y() &&
      $this->z() == $geometry->z()
    );
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

