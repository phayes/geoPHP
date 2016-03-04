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
  public function __construct($components = array()) {
    if (!is_array($components)) {
      throw new Exception("Component geometries must be passed as an array");
    }
    foreach ($components as $component) {
      if ($component instanceof Geometry) {
        $this->components[] = $component;
      }
      else {
        throw new Exception("Cannot create a collection with non-geometries");
      }
    }
  }

  /**
   * Returns Collection component geometries
   *
   * If no parameters are provided it simply returns the array of components stored in this collection
   * instance.  If the types parameter is provided and contains a list of valid geometries as listed in
   * geoPHP::geometryTypes() then the list of components will be further broken down to match the types
   * provided.  For example, if $geometry->getComponents('Point') is called, then the collection
   * will be recursively broken down until all geometries have been broken down into points.  A common
   * use case may be to call $geometry->getComponents(array('Point', 'LineString', 'Polygon')) to break
   * a collection down to the three basic geometry types.
   *
   * @param types an array of strings matching valid features types as listed in geoPHP::geometryTypes()
   * @return array
   */
  public function getComponents($types = array()) {
    // If no parameters are provided behave as always and return the components stored in this instance.
    if (!$types) {
      return $this->components;
    }

    // If a string is provided as a parameter place it into an array before continuing.
    if (is_string($types)) {
      $types = array($types);
    }

    // If the provided parameter is neither a string nor an array do nothing.
    if (is_array($types)) {
      // Make sure that the types array contains at least the Point geometry type.
      if (!in_array('Point', $types)) {
	$types[] = 'Point';
      }
      return $this->getComponentsRecursive($types);
    }
  }

  private function getComponentsRecursive($types = array('Point')) {
    // If the current geometry is of a type in the types array we can return it.
    if (in_array($this->geometryType(), $types)) {
      return array($this);
    }

    $components = array();
    foreach ($this->components as $component) {
      // Simply add geometries that match the given types to the return array to avoid calling
      // nonexistent getComponentsRecursive method on Point geometries.
      if (in_array($component->geometryType(), $types)) {
	$components[] = $component;
      } else {
	$components = array_merge($components, $component->getComponentsRecursive($types));
      }
    }

    return $components;
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
	for($i=0;$i<count($this->components);$i++)
	{
		if( method_exists($this->components[$i], 'invertxy' ) )
			$this->components[$i]->invertxy();
	}
  }

  public function centroid() {
    if ($this->isEmpty()) return NULL;

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
    if ($this->isEmpty()) return NULL;

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
    if ($this->isEmpty()) return new LineString();

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
      return TRUE;
    }
    else {
      foreach ($this->components as $component) {
        if (!$component->isEmpty()) return FALSE;
      }
      return TRUE;
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
      $points = array_merge($points, $component->getPoints());
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
      return FALSE;
    }

    foreach ($this_points as $point) {
      $found_match = FALSE;
      foreach ($other_points as $key => $test_point) {
        if ($point->equals($test_point)) {
          $found_match = TRUE;
          unset($other_points[$key]);
          break;
        }
      }
      if (!$found_match) {
        return FALSE;
      }
    }

    // All points match, return TRUE
    return TRUE;
  }

  public function isSimple() {
    if ($this->geos()) {
      return $this->geos()->isSimple();
    }

    // A collection is simple if all it's components are simple
    foreach ($this->components as $component) {
      if (!$component->isSimple()) return FALSE;
    }

    return TRUE;
  }

  public function explode() {
    $parts = array();
    foreach ($this->components as $component) {
      $parts = array_merge($parts, $component->explode());
    }
    return $parts;
  }

  // Not valid for this geometry type
  // --------------------------------
  public function x()                { return NULL; }
  public function y()                { return NULL; }
  public function startPoint()       { return NULL; }
  public function endPoint()         { return NULL; }
  public function isRing()           { return NULL; }
  public function isClosed()         { return NULL; }
  public function pointN($n)         { return NULL; }
  public function exteriorRing()     { return NULL; }
  public function numInteriorRings() { return NULL; }
  public function interiorRingN($n)  { return NULL; }
  public function pointOnSurface()   { return NULL; }
}

