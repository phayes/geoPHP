<?php

/**
 * Point: The most basic geometry type. All other geometries
 * are built out of Points.
 */
class Point extends Geometry
{
  public $coords = array(2);
  protected $geom_type = 'Point';
  protected $dimension = 2;

  /**
   * Constructor
   *
   * @param numeric $x The x coordinate (or longitude)
   * @param numeric $y The y coordinate (or latitude)
   * @param numeric $z The z coordinate (or altitude) - optional
   */
  public function __construct($x = NULL, $y = NULL, $z = NULL) {

    // Check if it's an empty point
    if ($x === NULL && $y === NULL) {
      $this->coords = array(NULL, NULL);
      $this->dimension = 0;
      return;
    }

    // Basic validation on x and y
    if (!is_numeric($x) || !is_numeric($y)) {
      throw new Exception("Cannot construct Point. x and y should be numeric");
    }

    // Check to see if this is a 3D point
    if ($z !== NULL) {
      if (!is_numeric($z)) {
       throw new Exception("Cannot construct Point. z should be numeric");
      }
      $this->dimension = 3;
    }

    // Convert to floatval in case they are passed in as a string or integer etc.
    $x = floatval($x);
    $y = floatval($y);
    $z = floatval($z);

    // Add poitional elements
    if ($this->dimension == 2) {
      $this->coords = array($x, $y);
    }
    if ($this->dimension == 3) {
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
    if ($this->dimension == 3) {
      return $this->coords[2];
    }
    else return NULL;
  }

  /**
   * Author : Adam Cherti
   * inverts x and y coordinates
   * Useful with old applications still using lng lat
   *
   * @return void
   * */
  public function invertxy()
  {
	$x=$this->coords[0];
	$this->coords[0]=$this->coords[1];
	$this->coords[1]=$x;
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
    if ($this->dimension == 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function numPoints() {
    return 1;
  }

  public function getPoints() {
    return array($this);
  }

  public function equals($geometry) {
    if (get_class($geometry) != 'Point') {
      return FALSE;
    }
    if (!$this->isEmpty() && !$geometry->isEmpty()) {
      /**
       * @see: http://php.net/manual/en/function.bccomp.php
       * @see: http://php.net/manual/en/language.types.float.php
       * @see: http://tubalmartin.github.io/spherical-geometry-php/#LatLng
       */
      return (abs($this->x() - $geometry->x()) <= 1.0E-9 && abs($this->y() - $geometry->y()) <= 1.0E-9);
    }
    else if ($this->isEmpty() && $geometry->isEmpty()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
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

