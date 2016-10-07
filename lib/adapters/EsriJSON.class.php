<?php

/**
 * EsriJSON class : a esrijson/argcis reader/writer.
 *
 * Note that it will always return a EsriJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 *
 * Disclaimer: This code is heavily based on the Terraformer ArcGis parser
 * javascript code:
 * https://github.com/Esri/terraformer-arcgis-parser
 */
class EsriJSON extends GeoAdapter {

  /**
   * Given an object or a string, return a Geometry
   *
   * @param mixed $input The EsriJSON string or object
   *
   * @return object Geometry
   */
  public function read($input) {
    if (is_string($input)) {
      $input = json_decode($input);
    }
    if (!is_object($input)) {
      throw new Exception('Invalid JSON');
    }

    // TODO: What if the wkid is different from 4326?
    //$inputSpatialReference = isset($input->geometry) ? $input->geometry->spatialReference : $input->spatialReference;

    if (property_exists($input, 'x') && property_exists($input, 'y')) {
      $coords = array($input->x, $input->y);
      if (property_exists($input, 'z')) {
        $coords[] = $input->z;
      }
      return $this->arrayToPoint($coords);
    }

    if (isset($input->points) && $input->points) {
      return $this->arrayToMultiPoint($input->points);
    }

    if (isset($input->paths) && $input->paths) {
      if (count($input->paths) === 1) {
        return $this->arrayToLineString($input->paths[0]);
      }
      else {
        return $this->arrayToMultiLineString($input->paths);
      }
    }

    if (property_exists($input, 'rings')) {
      return $this->convertRingsToGeometry($input->rings);
    }

    if ((isset($input->compressedGeometry) && $input->compressedGeometry) || (isset($input->geometry)) && $input->geometry) {
      if ($input->compressedGeometry) {
        $input->geometry = (object) array(
              'paths' => array(
                $this->decompressGeometry($input->compressedGeometry)
              ),
        );
      }
      return $this->read($input->geometry);
    }
    if ((isset($input->features)) && $input->features) {
      $geometries = array();
      foreach ($input->features as $feature) {
        $geometries[] = $this->read($feature);
      }
      return new GeometryCollection($geometries);
    }
    // Should have returned something by now.
    throw new Exception('Invalid JSON');
  }

  protected function arrayToPoint($array) {
    return new Point(
      isset($array[0]) ? $array[0] : NULL,
      isset($array[1]) ? $array[1] : NULL,
      isset($array[2]) ? $array[2] : NULL
    );
  }

  protected function arrayToMultiPoint($array) {
    $points = array();
    foreach ($array as $comp_array) {
      $points[] = $this->arrayToPoint($comp_array);
    }
    return new MultiPoint($points);
  }

  protected function arrayToLineString($array) {
    $points = array();
    foreach ($array as $comp_array) {
      $points[] = $this->arrayToPoint($comp_array);
    }
    return new LineString($points);
  }

  protected function arrayToPolygon($array) {
    $lines = array();
    foreach ($array as $comp_array) {
      $lines[] = $this->arrayToLineString($comp_array);
    }
    return new Polygon($lines);
  }

  protected function arrayToMultiLineString($array) {
    $lines = array();
    foreach ($array as $comp_array) {
      $lines[] = $this->arrayToLineString($comp_array);
    }
    return new MultiLineString($lines);
  }

  protected function arrayToMultiPolygon($array) {
    $polys = array();
    foreach ($array as $comp_array) {
      $polys[] = $this->arrayToPolygon($comp_array);
    }
    return new MultiPolygon($polys);
  }

  /**
   * Serializes an object into a geojson string
   *
   *
   * @param Geometry $obj The object to serialize
   *
   * @return string The GeoJSON string
   */
  public function write(Geometry $geometry, $return_array = FALSE) {
    if ($return_array) {
      return $this->getArray($geometry);
    }
    else {
      return json_encode($this->getArray($geometry));
    }
  }

  protected function getArray(Geometry $geometry) {
    $result = array('spatialReference' => (object) array('wkid' => 4326));
    switch ($geometry->geometryType()) {
      case "Point":
        $result['x'] = $geometry->x();
        $result['y'] = $geometry->y();
        if ($geometry->hasZ()) {
          $result['z'] = $geometry->z();
        }
        break;
      case "MultiPoint":
        $result['points'] = $geometry->asArray();
        break;
      case "LineString":
        $result['paths'] = array($geometry->asArray());
        break;
      case "MultiLineString":
        $result['paths'] = $geometry->asArray();
        break;
      case "Polygon":
        $result['rings'] = $this->orientRings($geometry->asArray());
        break;
      case "MultiPolygon":
        $result['rings'] = $this->flattenMultiPolygonRings($geometry->asArray());
        break;
      case "GeometryCollection":
        $result['features'] = array();
        foreach ($geometry->getComponents() as $component) {
          $result['features'][] = $this->getArray($component);
        }
        break;
    }

    return $result;
  }

  /**
   * Decompresses compressed geometry.
   *
   *
   * @param string $str The compressed geometry.
   * @return array The decompressed geometry points.
   */
  protected function decompressGeometry($str) {
    $xDiffPrev = 0;
    $yDiffPrev = 0;
    $points = array();
    $strings = array();
    // Split the string into an array on the + and - characters
    $string = preg_match_all('/((\+|\-)[^\+\-]+)/', $str, $strings);

    // The first value is the coefficient in base 32
    $coefficient = intval($strings[0], 32);

    for ($i = 1; $i < count($strings); $i += 2) {
      // $i is the offset for the $x value
      // Convert the value from base 32 and add the previous $x value
      $x = (intval($strings[$i], 32) + $xDiffPrev);
      $xDiffPrev = $x;

      // j+1 is the offset for the y value
      // Convert the value from base 32 and add the previous y value
      $y = (intval($strings[i + 1], 32) + $yDiffPrev);
      $yDiffPrev = $y;
      array_push($points, array($x / $coefficient, $y / $coefficient));
    }

    return $points;
  }

  /**
   * Checks if the first and last points of a ring are equal and closes the
   * ring if not.
   *
   *
   * @param array $coordinates The coordinates of the ring.
   * @return array The coordinates of the closed ring.
   */
  protected function closeRing($coordinates) {
    if (!$this->pointsEqual($coordinates[0], $coordinates[count($coordinates) - 1])) {
      array_push($coordinates, $coordinates[0]);
    }
    return $coordinates;
  }

  /**
   * Checks if 2 x,y points are equal.
   *
   *
   * @param array $a The coordinates of point a.
   * @param array $b The coordinates of point b.
   * @return boolean Whether or not the points are equal.
   */
  protected function pointsEqual($a, $b) {
    for ($i = 0; $i < count($a); $i++) {
      if ($a[$i] !== $b[$i]) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Determine if polygon ring coordinates are clockwise. Clockwise signifies
   * outer ring, counter-clockwise an inner ring or hole. This logic was found
   * at http://stackoverflow.com/questions/1165647/how-to-determine-if-a-list-
   * of-polygon-points-are-in-clockwise-order
   *
   *
   * @param array $ringToTest The coordinates of the ring to test.
   * @return boolean Wether or not the ring is clockwise.
   */
  protected function ringIsClockwise($ringToTest) {
    $total = 0;
    $rLength = count($ringToTest);
    $pt1 = $ringToTest[0];
    for ($i = 0; $i < $rLength - 1; $i++) {
      $pt2 = $ringToTest[$i + 1];
      $total += ($pt2[0] - $pt1[0]) * ($pt2[1] + $pt1[1]);
      $pt1 = $pt2;
    }
    return ($total >= 0);
  }

  /**
   * Ensures that rings are oriented in the right directions outer rings are
   * clockwise, holes are counterclockwise.
   *
   *
   * @param array $polygon The rings to orient.
   * @return array The oriented rings.
   */
  protected function orientRings($polygon) {
    $output = array();
    $outerRing = $this->closeRing(array_shift($polygon));
    if (count($outerRing) >= 4) {
      if (!$this->ringIsClockwise($outerRing)) {
        $outerRing = array_reverse($outerRing);
      }

      array_push($output, $outerRing);

      for ($i = 0; $i < count($polygon); $i++) {
        $hole = $this->closeRing($polygon[$i]);
        if (count($hole) >= 4) {
          if ($this->ringIsClockwise($hole)) {
            $hole = array_reverse($hole);
          }
          array_push($output, $hole);
        }
      }
    }
    return $output;
  }

  /** This function flattens holes in multipolygons to one array of polygons.
   * [
   *   [
   *     [ array of outer coordinates ]
   *     [ hole coordinates ]
   *     [ hole coordinates ]
   *   ],
   *   [
   *     [ array of outer coordinates ]
   *     [ hole coordinates ]
   *     [ hole coordinates ]
   *   ],
   * ]
   * becomes
   * [
   *   [ array of outer coordinates ]
   *   [ hole coordinates ]
   *   [ hole coordinates ]
   *   [ array of outer coordinates ]
   *   [ hole coordinates ]
   *   [ hole coordinates ]
   * ]
   *
   *
   * @param array $rings The rings to flatten.
   * @return array The flattened rings.
   */
  protected function flattenMultiPolygonRings($rings) {
    $output = array();
    for ($i = 0; $i < count($rings); $i++) {
      $polygon = $this->orientRings($rings[$i]);
      for ($j = count($polygon) - 1; $j >= 0; $j--) {
        array_push($output, $polygon[$j]);
      }
    }
    return $output;
  }

  /**
   * Checks if two edges intersect.
   *
   *
   * @param array $a1 First coordinate of the first edge to check.
   * @param array $a2 Second coordinate of the first edge to check.
   * @param array $b1 First coordinate of the second edge to check.
   * @param array $b2 Second coordinate of the second edge to check.
   * @return boolean Whether or not the edges intersect.
   */
  protected function edgeIntersectsEdge($a1, $a2, $b1, $b2) {
    $ua_t = ($b2[0] - $b1[0]) * ($a1[1] - $b1[1]) - ($b2[1] - $b1[1]) * ($a1[0] - $b1[0]);
    $ub_t = ($a2[0] - $a1[0]) * ($a1[1] - $b1[1]) - ($a2[1] - $a1[1]) * ($a1[0] - $b1[0]);
    $u_b = ($b2[1] - $b1[1]) * ($a2[0] - $a1[0]) - ($b2[0] - $b1[0]) * ($a2[1] - $a1[1]);

    if ($u_b != 0) {
      $ua = $ua_t / $u_b;
      $ub = $ub_t / $u_b;

      if (0 <= $ua && $ua <= 1 && 0 <= $ub && $ub <= 1) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if an array of coordinates intersect.
   *
   * @param array $a The first array of coordinates to check.
   * @param array $b The second array of coordinates to check.
   * @return boolean Whether or not the arrays of coordinates intersect.
   */
  protected function arraysIntersectArrays($a, $b) {
    if (is_numeric($a[0][0])) {
      if (is_numeric($b[0][0])) {
        for ($i = 0; $i < count($a) - 1; $i++) {
          for ($j = 0; $j < count($b) - 1; $j++) {
            if ($this->edgeIntersectsEdge($a[$i], $a[$i + 1], $b[$j], $b[$j + 1])) {
              return TRUE;
            }
          }
        }
      }
      else {
        for ($k = 0; $k < count($b); $k++) {
          if ($this->arraysIntersectArrays($a, $b[$k])) {
            return TRUE;
          }
        }
      }
    }
    else {
      for ($l = 0; $l < count($a); $l++) {
        if ($this->arraysIntersectArrays($a[$l], $b)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Check if an array of coordinates contain a point.
   *
   *
   * @param array $coordinates The array of coordinates to check.
   * @param array $point The coordinates of the point to check.
   * @return boolean Whether or not the array of coordinates contain the point.
   */
  protected function coordinatesContainPoint($coordinates, $point) {
    $contains = FALSE;
    for ($i = -1, $l = count($coordinates), $j = $l - 1; ++$i < $l; $j = $i) {
      if ((($coordinates[$i][1] <= $point[1] && $point[1] < $coordinates[$j][1]) ||
          ($coordinates[$j][1] <= $point[1] && $point[1] < $coordinates[$i][1])) &&
          ($point[0] < ($coordinates[$j][0] - $coordinates[$i][0]) * ($point[1] - $coordinates[$i][1]) / ($coordinates[$j][1] - $coordinates[$i][1]) + $coordinates[$i][0])) {
        $contains = !$contains;
      }
    }
    return $contains;
  }

  /**
   * Checks if an array of coordinates contains an other array of coordinates.
   *
   *
   * @param array $outer The array of outer coordinates.
   * @param array $inner The array of inner coordinates.
   * @return boolean Whether or not the outer array contains the inner array.
   */
  protected function coordinatesContainCoordinates($outer, $inner) {
    $intersects = $this->arraysIntersectArrays($outer, $inner);
    $contains = $this->coordinatesContainPoint($outer, $inner[0]);
    if (!$intersects && $contains) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if any polygons in this array contain any other polygons in this
   * array. Used for checking holes in arcgis rings.
   *
   *
   * @param array $rings The argcis rings to check.
   * @return Geometry The converted rings.
   */
  protected function convertRingsToGeometry($rings) {
    $outerRings = array();
    $holes = array();

    // For each ring.
    for ($r = 0; $r < count($rings); $r++) {
      $ring = $this->closeRing($rings[$r]);
      if (count($ring) < 4) {
        continue;
      }
      // Is this ring an outer ring? Is it clockwise?
      if ($this->ringIsClockwise($ring)) {
        $polygon = array($ring);
        // Push to outer rings.
        array_push($outerRings, $polygon);
      }
      else {
        // Counterclockwise push to holes.
        array_push($holes, $ring);
      }
    }

    $uncontainedHoles = array();

    // While there are holes left...
    while (count($holes)) {
      // Pop a hole off out stack.
      $hole = array_pop($holes);

      // Loop over all outer rings and see if they contain our hole.
      $contained = FALSE;
      for ($x = count($outerRings) - 1; $x >= 0; $x--) {
        $outerRing = $outerRings[$x][0];
        if ($this->coordinatesContainCoordinates($outerRing, $hole)) {
          // The hole is contained push it into our polygon.
          array_push($outerRings[$x], $hole);
          $contained = TRUE;
          break;
        }
      }

      // The ring is not contained in any outer ring.
      // Sometimes this happens https://github.com/Esri/esri-leaflet/issues/320
      if (!$contained) {
        array_push($uncontainedHoles, $hole);
      }
    }

    // If we couldn't match any holes using contains we can now try intersects...
    while (count($uncontainedHoles)) {
      // Pop a hole off out stack.
      $hole = array_pop($uncontainedHoles);

      // Loop over all outer rings and see if any intersect our hole.
      $intersects = FALSE;
      for ($x = count($outerRings) - 1; $x >= 0; $x--) {
        $outerRing = $outerRings[$x][0];
        if ($this->arraysIntersectArrays($outerRing, $hole)) {
          // The hole intersects the outer ring push it into our polygon.
          array_push($outerRings[$x], $hole);
          $intersects = TRUE;
          break;
        }
      }

      // Hole does not intersect ANY outer ring at this point.
      // Make it an outer ring.
      if (!$intersects) {
        array_push($outerRings, array(array_reverse(hole)));
      }
    }

    if (count($outerRings) === 1) {
      return $this->arrayToPolygon($outerRings[0]);
    }
    else {
      return $this->arrayToMultiPolygon($outerRings);
    }
  }
}
