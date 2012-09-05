<?php

/**
 * Geometry abstract class
 */
abstract class Geometry
{
  private   $geos = NULL;
  protected $srid = NULL;
  protected $geom_type;
  protected $dimention = 2; // or dimension ?
  protected $measured = false;
  
  // Abtract: Standard
  // -----------------
  abstract public function area();
  abstract public function boundary();
  abstract public function centroid();
  abstract public function length();
  abstract public function length3D();
  abstract public function numGeometries();
  abstract public function geometryN($n);
  abstract public function startPoint();
  abstract public function endPoint();
  abstract public function isRing();            // Mssing dependancy
  abstract public function isClosed();          // Missing dependancy
  abstract public function numPoints();
  abstract public function pointN($n);
  abstract public function exteriorRing();
  abstract public function numInteriorRings();
  abstract public function interiorRingN($n);
  abstract public function dimension();
  abstract public function distance(Geometry $geom);
  abstract public function equals($geom);
  abstract public function isEmpty();
  abstract public function isSimple();

  // Abtract: Non-Standard
  // ---------------------
  abstract public function getBBox();
  abstract public function asArray();
  abstract public function getPoints();
  abstract public function explode(); // Get all line segments
  abstract public function greatCircleLength(); //meters
  abstract public function haversineLength(); //degrees
  abstract public function flatten(); // 3D to 2D

  
  // will be removed from here (this is for Point only)
  // i made it non abstract because there is no difference
  public function x() {return null;}
  public function y() {return null;}
  public function z()	{return null;}
  public function m()	{return null;}
  public function getX() {return null;}
  public function getY() {return null;}


  // Public: Standard -- Common to all geometries
  // --------------------------------------------
  public function SRID() {
    return $this->srid;
  }

  public function setSRID($srid) {
    if ($this->geos()) {
      $this->geos()->setSRID($srid);
    }
    $this->srid = $srid;
  }

  public function envelope() {
    if ($this->isEmpty()) return new Polygon();

    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->envelope());
    }

    $bbox = $this->getBBox();
    $points = array (
      new Point($bbox['maxx'],$bbox['miny']),
      new Point($bbox['maxx'],$bbox['maxy']),
      new Point($bbox['minx'],$bbox['maxy']),
      new Point($bbox['minx'],$bbox['miny']),
      new Point($bbox['maxx'],$bbox['miny']),
    );

    $outer_boundary = new LineString($points);
    return new Polygon(array($outer_boundary));
  }

  public function geometryType() {
    return $this->geom_type;
  }

  public function hasZ() {
    if ($this->dimention == 3) {
      return TRUE;
    }
  }

  public function coordinateDimension() {
    return $this->dimention;
  }

  public function coordinateDimension() {
    return $this->dimention;
  }

  /**
   * check if is a 3D point
   *
   * @return true or NULL if is not a 3D point
   */
  public function hasZ() {
    if ($this->dimention == 3) {
      return TRUE;
    }
  }
  /**
   * set geometry have 3d value
   * 
   * @param bool 
   */
  public function set3d($bool) {
  	$this->dimention = ($bool) ? 3 : 2;
  }
  
  /**
   * check if is a measured value
   *
   * @return true or NULL if is a measured value
   */
  public function isMeasured() {
  	return $this->measured;
  }
  
  /**
   * set geometry have measured value
   * 
   * @param bool
   */
  public function setMeasured($bool) {
  	$this->measured = ($bool) ? true : false;
  }
  
  // Public: Non-Standard -- Common to all geometries
  // ------------------------------------------------

  // $this->out($format, $other_args);
  public function out() {
    $args = func_get_args();

    $format = array_shift($args);
    $type_map = geoPHP::getAdapterMap();
    $processor_type = $type_map[$format];
    $processor = new $processor_type();

    array_unshift($args, $this);
    $result = call_user_func_array(array($processor, 'write'), $args);
    
    return $result;
  }


  // Public: Aliases
  // ---------------
  public function getCentroid() {
    return $this->centroid();
  }

  public function getArea() {
    return $this->area();
  }

  public function getGeos() {
    return $this->geos();
  }

  public function getGeomType() {
    return $this->geometryType();
  }

  public function getSRID() {
    return $this->SRID();
  }

  public function asText() {
    return $this->out('wkt');
  }

  public function asBinary() {
    return $this->out('wkb');
  }

  public function is3D() {
    return $this->hasZ();
  }

  // Public: GEOS Only Functions
  // ---------------------------
  public function geos() {
    // If it's already been set, just return it
    if ($this->geos && geoPHP::geosInstalled()) {
      return $this->geos;
    }
    // It hasn't been set yet, generate it
    if (geoPHP::geosInstalled()) {
      $reader = new GEOSWKBReader();
      $this->geos = $reader->readHEX($this->out('wkb',TRUE));
    }
    else {
      $this->geos = FALSE;
    }
    return $this->geos;
  }

  public function setGeos($geos) {
    $this->geos = $geos;
  }

  public function pointOnSurface() {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->pointOnSurface());
    }
  }

  public function equalsExact(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->equalsExact($geometry->geos());
    }
  }

  public function relate(Geometry $geometry, $pattern = NULL) {
    if ($this->geos()) {
      if ($pattern) {
        return $this->geos()->relate($geometry->geos(), $pattern);
      }
      else {
        return $this->geos()->relate($geometry->geos());
      }
    }
  }

  public function checkValidity() {
    if ($this->geos()) {
      return $this->geos()->checkValidity();
    }
  }

  public function buffer($distance) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->buffer($distance));
    }
  }

  public function intersection(Geometry $geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->intersection($geometry->geos()));
    }
  }

  public function convexHull() {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->convexHull());
    }
  }

  public function difference(Geometry $geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->difference($geometry->geos()));
    }
  }

  public function symDifference(Geometry $geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->symDifference($geometry->geos()));
    }
  }

  // Can pass in a geometry or an array of geometries
  public function union(Geometry $geometry) {
    if ($this->geos()) {
      if (is_array($geometry)) {
        $geom = $this->geos();
        foreach ($geometry as $item) {
          $geom = $geom->union($item->geos());
        }
        return geoPHP::geosToGeometry($geos);
      }
      else {
        return geoPHP::geosToGeometry($this->geos()->union($geometry->geos()));
      }
    }
  }

  public function simplify($tolerance, $preserveTopology = FALSE) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->simplify($tolerance, $preserveTopology));
    }
  }

  public function disjoint(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->disjoint($geometry->geos());
    }
  }

  public function touches(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->touches($geometry->geos());
    }
  }

  public function intersects(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->intersects($geometry->geos());
    }
  }

  public function crosses(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->crosses($geometry->geos());
    }
  }

  public function within(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->within($geometry->geos());
    }
  }

  public function contains(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->contains($geometry->geos());
    }
  }

  public function overlaps(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->overlaps($geometry->geos());
    }
  }

  public function covers(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->covers($geometry->geos());
    }
  }

  public function coveredBy(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->coveredBy($geometry->geos());
    }
  }

  public function hausdorffDistance(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->hausdorffDistance($geometry->geos());
    }
  }

  public function project(Geometry $point, $normalized = NULL) {
    if ($this->geos()) {
      return $this->geos()->project($point->geos(), $normalized);
    }
  }
}