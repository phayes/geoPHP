<?php

/**
 * Collection: Abstract class for compound geometries
 * 
 * A geometry is a collection if it is made up of other
 * component geometries. Therefore everything but a Point
 * is a Collection. For example a LingString is a collection
 * of Points. A Polygon is a collection of LineStrings etc.
 */
abstract class Collection extends Geometry
{
  public $components = array();
  
  /**
   * Constructor: Checks and sets component geometries
   *
   * @param array $components array of geometries
   */
  public function __construct(array $components) {
    foreach ($components as $component) {
      if ($component instanceof Geometry) {
        $this->components[] = $component;
      }
      else {
        throw new Exception("Cannot create empty collection");
      }
    }
    
    if (empty($this->components)) {
      throw new Exception("Cannot create empty ".get_called_class());
    }
  }
  
  /**
   * Returns Collection component geometries
   *
   * @return array
   */
  public function getComponents() {
    return $this->components;
  }
  
  public function centroid() {
    if ($this->geos()) {
      $geos_centroid = $this->geos()->centroid();
      if ($geos_centroid->typeName() == 'Point') {
        return geoPHP::geosToGeometry($this->geos()->centroid());
      }
    }
    
    // As a rough estimate, we say that the centroid of a colletion is the centroid of it's envelope
    // @@TODO: Make this the centroid of the convexHull
    // Note: Outside of polygons, geometryCollections and the trivial case of points, there is no standard on what a "centroid" is
    $centroid = $this->envelope()->centroid();
    
    return $centroid;
  }
  
  public function getBBox() {
    if ($this->geos()) {
      $envelope = $this->geos()->envelope();
      if ($envelope->typeName() == 'Point') {
        return geoPHP::geosToGeometry($envelope)->getBBOX();
      }
      
      $geos_ring = $envelope->exteriorRing();
      return array(
        'maxy' => $geos_ring->pointN(3)->getY(),
        'miny' => $geos_ring->pointN(1)->getY(),
        'maxx' => $geos_ring->pointN(1)->getX(),
        'minx' => $geos_ring->pointN(3)->getX(),
      );
    }
    
    // Go through each component and get the max and min x and y
    $i = 0;
    foreach ($this->components as $component) {
      $component_bbox = $component->getBBox();
      
      // On the first run through, set the bbox to the component bbox
      if ($i == 0) {
        $maxx = $component_bbox['maxx'];
        $maxy = $component_bbox['maxy'];
        $minx = $component_bbox['minx'];
        $miny = $component_bbox['miny'];
      }
      
      // Do a check and replace on each boundary, slowly growing the bbox
      $maxx = $component_bbox['maxx'] > $maxx ? $component_bbox['maxx'] : $maxx;
      $maxy = $component_bbox['maxy'] > $maxy ? $component_bbox['maxy'] : $maxy;
      $minx = $component_bbox['minx'] < $minx ? $component_bbox['minx'] : $minx;
      $miny = $component_bbox['miny'] < $miny ? $component_bbox['miny'] : $miny;
      $i++;
    }
    
    return array(
      'maxy' => $maxy,
      'miny' => $miny,
      'maxx' => $maxx,
      'minx' => $minx,
    );
  }
  
  public function asArray() {
    $array = array();
    foreach ($this->components as $component) {
      $array[] = $component->asArray();
    }
    return $array;
  }

  public function area() {
    if ($this->geos()) {
      return $this->geos()->area();
    }
    
    $area = 0;
    foreach ($this->components as $component) {
      $area += $component->area();
    }
    return $area;
  }

  // By default, the boundary of a collection is the boundary of it's components
  public function boundary() {
    if ($this->geos()) {
      return $this->geos()->boundary();
    }
    
    $components_boundaries = array();
    foreach ($this->components as $component) {
      $components_boundaries[] = $component->boundary();
    }
    return geoPHP::geometryReduce($components_boundaries);
  }
  
  public function numGeometries() {
    return count($this->components);
  }
  
  // Note that the standard is 1 based indexing
  public function geometryN($n) {
    $n = intval($n);
    if (array_key_exists($n-1, $this->components)) {
      return $this->components[$n-1];
    }
    else {
      return NULL;
    }
  }
  
  public function length() {
    if ($this->geos()) {
      return $this->geos()->length();
    }
    
    $length = 0;
    foreach ($this->components as $delta => $point) {
      $next_point = $this->geometryN($delta);
      if ($next_point) {
        // Pythagorean Theorem
        $distance = sqrt(($next_point->getX() - $point->getX())^2+($next_point->getY()- $point->getY())^2);
        $length += $distance;
      }
    }
    return $length;
  }
  
  public function dimension() {
    $dimension = 0;
    foreach ($this->components as $component) {
      if ($component->dimension() > $dimension) {
        $dimension = $component->dimension();
      }
    }
    return $dimension;
  }
  
  
  // Not valid for this geometry type
  // --------------------------------
  public function x()                { return NULL; }
  public function y()                { return NULL; }
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

