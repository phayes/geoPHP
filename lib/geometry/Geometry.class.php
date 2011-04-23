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
 * Geometry : abstract class which represents a geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
abstract class Geometry 
{
  private   $geos = NULL;
  protected $geom_type;
  protected $srid;
  
  
  // Abtract: Standard
  // -----------------
  abstract public function area();
  abstract public function boundary();
  abstract public function centroid();
  abstract public function length();
  abstract public function y();
  abstract public function x();
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
  
  
  // Abtract: Non-Standard
  // ---------------------
  abstract public function getCoordinates();
  abstract public function getBBox();
  
  
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
  
  // Public: Non-Standard -- Common to all geometries
  // ------------------------------------------------
  public function getGeoInterface() {
    return array(
      'type'=> $this->getGeomType(),
      'coordinates'=> $this->getCoordinates()
    );
  }
  
  // $this->out($format, $other_args);
  public function out() {
    $args = func_get_args();
    
    $format = array_shift($args);
    $type_map = geoPHP::getAdapterMap();
    $processor_type = $type_map[$format];
    $processor = new $processor_type();

    // @@TODO: Hack alert!
    // There has got to be a better way to do this...
    // Is there an equivilent to call_user_func for objects???
    if (count($args) == 0) $result = $processor->write($this);
    if (count($args) == 1) $result = $processor->write($this, $args[0]);
    if (count($args) == 2) $result = $processor->write($this, $args[0],$args[1]);
    if (count($args) == 3) $result = $processor->write($this, $args[0],$args[1],$args[2]);
    if (count($args) == 4) $result = $processor->write($this, $args[0],$args[1],$args[2], $args[3]);
    if (count($args) == 5) $result = $processor->write($this, $args[0],$args[1],$args[2], $args[3], $args[4]);

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

  public function getX() {
    return $this->x();
  }
  
  public function getY() {
    return $this->y();
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
  
  // Public: GEOS Only Functions
  // ---------------------------
  public function geos() {
    // If it's already been set, just return it
    if ($this->geos !== NULL) {
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
  
  public function equals($geometry) {
    if ($this->geos()) {
      return $this->geos()->equals($geometry->geos());
    }
  }
  
  public function equalsExact($geometry) {
    if ($this->geos()) {
      return $this->geos()->equalsExact($geometry->geos());
    }
  }
  
  public function relate($geometry) {
    //@@TODO: Deal with second "pattern" parameter
    if ($this->geos()) {
      return $this->geos()->relate($geometry->geos());
    }
  }
  
  public function checkValidity() {
    if ($this->geos()) {
      return $this->geos()->checkValidity();
    }
  }

  public function isSimple() {
    if ($this->geos()) {
      return $this->geos()->isSimple();
    }
  }
  
  public function buffer($distance) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->buffer($distance));
    }
  }
  
  public function intersection($geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->intersection($geometry->geos()));
    }
  }
  
  public function convexHull() {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->convexHull());
    }
  }
  
  public function difference($geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->difference($geometry->geos()));
    }
  }
  
  public function symDifference($geometry) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->symDifference($geometry->geos()));
    }
  }
  
  public function union($geometry) {
    //@@TODO: also does union cascade
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->union($geometry->geos()));
    }
  }
  
  public function simplify($tolerance, $preserveTopology = FALSE) {
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->simplify($tolerance, $preserveTopology));
    }
  }
  
  public function disjoint($geometry) {
    if ($this->geos()) {
      return $this->geos()->disjoint($geometry->geos());
    }
  }
  
  public function touches($geometry) {
    if ($this->geos()) {
      return $this->geos()->touches($geometry->geos());
    }
  }
  
  public function intersects($geometry) {
    if ($this->geos()) {
      return $this->geos()->intersects($geometry->geos());
    }
  }
  
  public function crosses($geometry) {
    if ($this->geos()) {
      return $this->geos()->crosses($geometry->geos());
    }
  }

  public function within($geometry) {
    if ($this->geos()) {
      return $this->geos()->within($geometry->geos());
    }
  }
  
  public function contains($geometry) {
    if ($this->geos()) {
      return $this->geos()->contains($geometry->geos());
    }
  }
  
  public function overlaps($geometry) {
    if ($this->geos()) {
      return $this->geos()->overlaps($geometry->geos());
    }
  }
  
  public function covers($geometry) {
    if ($this->geos()) {
      return $this->geos()->covers($geometry->geos());
    }
  }
  
  public function coveredBy($geometry) {
    if ($this->geos()) {
      return $this->geos()->coveredBy($geometry->geos());
    }
  }

  public function distance($geometry) {
    if ($this->geos()) {
      return $this->geos()->distance($geometry->geos());
    }
  }
  
  public function hausdorffDistance($geometry) {
    if ($this->geos()) {
      return $this->geos()->hausdorffDistance($geometry->geos());
    }
  }
  
  
  // Public - Placeholders
  // ---------------------
  public function hasZ() {
    // geoPHP does not support Z values at the moment
    return FALSE;  
  }
  
  public function is3D() {
    // geoPHP does not support 3D geometries at the moment
    return FALSE;  
  }
  
  public function isMeasured() {
    // geoPHP does not yet support M values
    return FALSE;
  }
  
  public function isEmpty() {
    // geoPHP does not yet support empty geometries
    return FALSE;
  }
  
  public function coordinateDimension() {
    // geoPHP only supports 2-dimentional space
    return 2;
  }
  
  public function z() {
    // geoPHP only supports 2-dimentional space
    return NULL;
  }

  public function m() {
    // geoPHP only supports 2-dimentional space
    return NULL;
  }
  
}
