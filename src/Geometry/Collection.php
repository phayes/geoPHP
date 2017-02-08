<?php

namespace Phayes\GeoPHP\Geometry;

use Phayes\GeoPHP\GeoPHP;
use Phayes\GeoPHP\Geometry\Geometry;
use Phayes\GeoPHP\Geometry\LineString;
use Exception;

abstract class Collection extends Geometry
{
    /** @var Geometry[] */
  public $components = [];

  /**
   * Constructor: Checks and sets component geometries
   *
   * @param array $components array of geometries
   */
  public function __construct($components = []) {

    if (!is_array($components)) {
      throw new Exception("Component geometries must be passed as an array");
    }

    foreach ($components as $component) {
      if ($component instanceof Geometry) {
        $this->components[] = $component;
      } else {
        throw new Exception("Cannot create a collection with non-geometries");
      }
    }
  }

  /**
   * Returns Collection component geometries
   *
   * @return Geometry[]
   */
  public function getComponents() {
    return $this->components;
  }


  /*
   * Author : Adam Cherti
   *
   * inverts x and y coordinates
   * Useful for old data still using lng lat
   *
   * @return void
   *
   * */
  public function invertxy()
  {
	  for($i=0;$i<count($this->components);$i++) {
		  if( method_exists($this->components[$i], 'invertxy' )) {
			  $this->components[$i]->invertxy();
      }
	  }
  }

  public function centroid() {
    if ($this->isEmpty()) return NULL;
    if ($this->geos()) {
      $geos_centroid = $this->geos()->centroid();
      if ($geos_centroid->typeName() == 'Point') {
        return GeoPHP::geosToGeometry($this->geos()->centroid());
      }
    }
    // As a rough estimate, we say that the centroid of a colletion is the centroid of it's envelope
    // @@TODO: Make this the centroid of the convexHull
    // Note: Outside of polygons, geometryCollections and the trivial case of points, there is no standard on what a "centroid" is
    $centroid = $this->envelope()->centroid();
    return $centroid;
  }

  public function getBBox() {

    if ($this->isEmpty()) return null;
    if ($this->geos()) {
      $envelope = $this->geos()->envelope();
      if ($envelope->typeName() == 'Point') {
        return GeoPHP::geosToGeometry($envelope)->getBBOX();
      }
      $geos_ring = $envelope->exteriorRing();
      return [
        'maxy' => $geos_ring->pointN(3)->getY(),
        'miny' => $geos_ring->pointN(1)->getY(),
        'maxx' => $geos_ring->pointN(1)->getX(),
        'minx' => $geos_ring->pointN(3)->getX(),
      ];
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

    return [
      'maxy' => $maxy,
      'miny' => $miny,
      'maxx' => $maxx,
      'minx' => $minx,
    ];
  }

  public function asArray() {
    $array = [];
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
    if ($this->isEmpty()) return new LineString();
    if ($this->geos()) {
      return $this->geos()->boundary();
    }
    $components_boundaries = [];
    foreach ($this->components as $component) {
      $components_boundaries[] = $component->boundary();
    }
    return GeoPHP::geometryReduce($components_boundaries);
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
      return null;
    }
  }

  public function length() {
    $length = 0;
    foreach ($this->components as $delta => $component) {
      $length += $component->length();
    }
    return $length;
  }

  public function greatCircleLength($radius = 6378137) {
    $length = 0;
    foreach ($this->components as $component) {
      $length += $component->greatCircleLength($radius);
    }
    return $length;
  }

  public function haversineLength() {
    $length = 0;
    foreach ($this->components as $component) {
      $length += $component->haversineLength();
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

  // A collection is empty if it has no components OR all it's components are empty
  public function isEmpty() {
    if (!count($this->components)) {
      return true;
    }
    else {
      foreach ($this->components as $component) {
        if (!$component->isEmpty()) return false;
      }
      return true;
    }
  }

  public function numPoints() {
    $num = 0;
    foreach ($this->components as $component) {
      $num += $component->numPoints();
    }
    return $num;
  }

  public function getPoints() {
    $points = array();
    foreach ($this->components as $component) {
      foreach ($component->getPoints() as $point) {
        $points[] = $point;
      }
    }
    return $points;
  }

  public function equals($geometry) {
    if ($this->geos()) {
      return $this->geos()->equals($geometry->geos());
    }
    // To test for equality we check to make sure that there is a matching point
    // in the other geometry for every point in this geometry.
    // This is slightly more strict than the standard, which
    // uses Within(A,B) = true and Within(B,A) = true
    // @@TODO: Eventually we could fix this by using some sort of simplification
    // method that strips redundant vertices (that are all in a row)
    $this_points = $this->getPoints();
    $other_points = $geometry->getPoints();
    // First do a check to make sure they have the same number of vertices
    if (count($this_points) != count($other_points)) {
      return false;
    }
    foreach ($this_points as $point) {
      $found_match = false;
      foreach ($other_points as $key => $test_point) {
        if ($point->equals($test_point)) {
          $found_match = true;
          unset($other_points[$key]);
          break;
        }
      }
      if (!$found_match) {
        return false;
      }
    }
    // All points match, return TRUE
    return true;
  }

  public function isSimple() {
    if ($this->geos()) {
      return $this->geos()->isSimple();
    }
    // A collection is simple if all it's components are simple
    foreach ($this->components as $component) {
      if (!$component->isSimple()) return false;
    }
    return true;
  }

  public function explode() {
    $parts = array();
    foreach ($this->components as $component) {
    	foreach ($component->explode() as $subComponent) {
		$parts[] = $subComponent;
	}
    }
    return $parts;
  }

  // Not valid for this geometry type
  // --------------------------------
  public function x()                { return null; }
  public function y()                { return null; }
  public function startPoint()       { return null; }
  public function endPoint()         { return null; }
  public function isRing()           { return null; }
  public function isClosed()         { return null; }
  public function pointN($n)         { return null; }
  public function exteriorRing()     { return null; }
  public function numInteriorRings() { return null; }
  public function interiorRingN($n)  { return null; }
  public function pointOnSurface()   { return null; }
}
