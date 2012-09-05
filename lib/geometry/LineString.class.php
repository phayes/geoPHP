<?php
/**
 * LineString. A collection of Points representing a line.
 * A line can have more than one segment.
 */
class LineString extends Collection
{
  protected $geom_type = 'LineString';
  protected $dimention = 2;

  /**
   * Constructor
   *
   * @param array $points An array of at least two points with
   * which to build the LineString
   */
  public function __construct($points = array()) {
    if (count($points) == 1) {
      throw new Exception("Cannot construct a LineString with a single point");
    }

    // Call the Collection constructor to build the LineString
    parent::__construct($points);
  }

  // The boundary of a linestring is itself
  public function boundary() {
    return $this;
  }

  public function startPoint() {
    return $this->pointN(1);
  }

  public function endPoint() {
    $last_n = $this->numPoints();
    return $this->pointN($last_n);
  }

  public function isClosed() {
    return ($this->startPoint()->equals($this->endPoint()));
  }

  public function isRing() {
    return ($this->isClosed() && $this->isSimple());
  }

  public function numPoints() {
    return $this->numGeometries();
  }

  public function pointN($n) {
    return $this->geometryN($n);
  }

  public function dimension() {
    if ($this->isEmpty()) return 0;
    return 1;
  }

  public function area() {
    return 0;
  }

  public function length() {
    if ($this->geos()) {
      return $this->geos()->length();
    }
    $length = 0;
    foreach ($this->getPoints() as $delta => $point) {
      $previous_point = $this->geometryN($delta);
      if ($previous_point) {
        $length += sqrt(pow(($previous_point->getX() - $point->getX()), 2) + pow(($previous_point->getY()- $point->getY()), 2));
      }
    }
    return $length;
  }

  public function length3D() {
    $length = 0;
    foreach ($this->getPoints() as $delta => $point) {
      $previous_point = $this->geometryN($delta);
      if ($previous_point) {
        $length += sqrt(pow(($previous_point->x() - $point->x()), 2) + pow(($previous_point->y() - $point->y()), 2) + pow(($previous_point->z() - $point->z()), 2));
      }
    }
    return $length;
  }

  public function greatCircleLength($radius = 6378137) {
    $length = 0;
    $points = $this->getPoints();
    for($i=0; $i<$this->numPoints()-1; $i++) {
      $point = $points[$i];
      $next_point = $points[$i+1];
      if (!is_object($next_point)) {continue;}
      // Great circle method
      $lat1 = deg2rad($point->getY());
      $lat2 = deg2rad($next_point->getY());
      $lon1 = deg2rad($point->getX());
      $lon2 = deg2rad($next_point->getX());
      $dlon = $lon2 - $lon1;
      $length +=
        $radius *
          atan2(
            sqrt(
              pow(cos($lat2) * sin($dlon), 2) +
                pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dlon), 2)
            )
            ,
            sin($lat1) * sin($lat2) +
              cos($lat1) * cos($lat2) * cos($dlon)
          );
    }
    // Returns length in meters.
    return $length;
  }

  public function haversineLength() {
    $degrees = 0;
    $points = $this->getPoints();
    for($i=0; $i<$this->numPoints()-1; $i++) {
      $point = $points[$i];
      $next_point = $points[$i+1];
      if (!is_object($next_point)) {continue;}
      $degree = rad2deg(
        acos(
          sin(deg2rad($point->getY())) * sin(deg2rad($next_point->getY())) +
            cos(deg2rad($point->getY())) * cos(deg2rad($next_point->getY())) *
              cos(deg2rad(abs($point->getX() - $next_point->getX())))
        )
      );
      $degrees += $degree;
    }
    // Returns degrees
    return $degrees;
  }

  public function explode() {
    $parts = array();
    $points = $this->getPoints();

    foreach ($points as $i => $point) {
      if (isset($points[$i+1])) {
        $parts[] = new LineString(array($point, $points[$i+1]));
      }
    }
    return $parts;
  }

  public function isSimple() {
    if ($this->geos()) {
      return $this->geos()->isSimple();
    }

    $segments = $this->explode();

    foreach ($segments as $i => $segment) {
      foreach ($segments as $j => $check_segment) {
        if ($i != $j) {
          if ($segment->lineSegmentIntersect($check_segment)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  // Utility function to check if any line sigments intersect
  // Derived from http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
  public function lineSegmentIntersect($segment) {
    $p0_x = $this->startPoint()->x();
    $p0_y = $this->startPoint()->y();
    $p1_x = $this->endPoint()->x();
    $p1_y = $this->endPoint()->y();
    $p2_x = $segment->startPoint()->x();
    $p2_y = $segment->startPoint()->y();
    $p3_x = $segment->endPoint()->x();
    $p3_y = $segment->endPoint()->y();

    $s1_x = $p1_x - $p0_x;     $s1_y = $p1_y - $p0_y;
    $s2_x = $p3_x - $p2_x;     $s2_y = $p3_y - $p2_y;

    $fps = (-$s2_x * $s1_y) + ($s1_x * $s2_y);
    $fpt = (-$s2_x * $s1_y) + ($s1_x * $s2_y);

    if ($fps == 0 || $fpt == 0) {
      return FALSE;
    }

    $s = (-$s1_y * ($p0_x - $p2_x) + $s1_x * ($p0_y - $p2_y)) / $fps;
    $t = ( $s2_x * ($p0_y - $p2_y) - $s2_y * ($p0_x - $p2_x)) / $fpt;

    if ($s > 0 && $s < 1 && $t > 0 && $t < 1) {
      // Collision detected
      return TRUE;
    }
    return FALSE;
  }

  public function distance(Geometry $geometry) {
    if ($this->geos()) {
      return $this->geos()->distance($geometry->geos());
    }

    if ($geometry->geometryType() == 'Point') {
      // This is defined in the Point class nicely
      return $geometry->distance($this);
    }
    if ($geometry->geometryType() == 'LineString') {
      $distance = NULL;
      foreach ($this->explode() as $seg1) {
        foreach ($geometry->explode() as $seg2) {
          if ($seg1->lineSegmentIntersect($seg2)) return 0;
          // Because line-segments are straight, the shortest distance will occur at an endpoint.
          // If they are parallel an endpoint calculation is still accurate.
          $check_distance_1 = $seg1->pointN(1)->distance($seg2);
          $check_distance_2 = $seg1->pointN(2)->distance($seg2);
          $check_distance_3 = $seg2->pointN(1)->distance($seg1);
          $check_distance_4 = $seg2->pointN(2)->distance($seg1);

          $check_distance = min($check_distance_1, $check_distance_2, $check_distance_3, $check_distance_4);
          if ($distance === NULL) $distance = $check_distance;
          if ($check_distance < $distance) $distance = $check_distance;
        }
      }
      return $distance;
    }
    else {
      // It can be treated as collection
      return parent::distance($geometry);
    }
  }
}
