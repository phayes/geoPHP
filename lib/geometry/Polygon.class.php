<?php

/**
 * Polygon: A polygon is a plane figure that is bounded by a closed path, 
 * composed of a finite sequence of straight line segments
 */
class Polygon extends Collection
{
  protected $geom_type = 'Polygon';
  
  public function area($exterior_only = FALSE, $signed = FALSE) {
    if ($this->isEmpty()) return 0;
    
    if ($this->geos() && $exterior_only == FALSE) {
      return $this->geos()->area();
    }
    
    $exterior_ring = $this->components[0];
    $pts = $exterior_ring->getComponents();
    
    $c = count($pts);
    if((int)$c == '0') return NULL;
    $a = '0';
    foreach($pts as $k => $p){
      $j = ($k + 1) % $c;
      $a = $a + ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
    }
    
    if ($signed) $area = ($a / 2);
    else $area = abs(($a / 2));
    
    if ($exterior_only == TRUE) {
      return $area;
    }
    foreach ($this->components as $delta => $component) {
      if ($delta != 0) {
        $inner_poly = new Polygon(array($component));
        $area -= $inner_poly->area();
      }
    }
    return $area;
  }
  
  public function centroid() {
    if ($this->isEmpty()) return NULL;
    
    if ($this->geos()) {
      return geoPHP::geosToGeometry($this->geos()->centroid());
    }
    
    $exterior_ring = $this->components[0];
    $pts = $exterior_ring->getComponents();
    
    $c = count($pts);
    if((int)$c == '0') return NULL;
    $cn = array('x' => '0', 'y' => '0');
    $a = $this->area(TRUE, TRUE);
    
    // If this is a polygon with no area. Just return the first point.
    if ($a == 0) {
      return $this->exteriorRing()->pointN(1);
    }
    
    foreach($pts as $k => $p){
      $j = ($k + 1) % $c;
      $P = ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
      $cn['x'] = $cn['x'] + ($p->getX() + $pts[$j]->getX()) * $P;
      $cn['y'] = $cn['y'] + ($p->getY() + $pts[$j]->getY()) * $P;
    }
    
    $cn['x'] = $cn['x'] / ( 6 * $a);
    $cn['y'] = $cn['y'] / ( 6 * $a);
    
    $centroid = new Point($cn['x'], $cn['y']);
    return $centroid;
  }

	/**
	 * Find the outermost point from the centroid
	 *
	 * @returns Point The outermost point
	 */
  public function outermostPoint() {
		$centroid = $this->getCentroid();

		$max = array('length' => 0, 'point' => null);

		foreach($this->getPoints() as $point) {
			$lineString = new LineString(array($centroid, $point));

			if($lineString->length() > $max['length']) {
				$max['length'] = $lineString->length();
				$max['point'] = $point;
			}
		}

		return $max['point'];
  }

  public function exteriorRing() {
    if ($this->isEmpty()) return new LineString();
    return $this->components[0];
  }
  
  public function numInteriorRings() {
    if ($this->isEmpty()) return 0;
    return $this->numGeometries()-1;
  }
  
  public function interiorRingN($n) {
    return $this->geometryN($n+1);
  }
  
  public function dimension() {
    if ($this->isEmpty()) return 0;
    return 2;
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

  // Not valid for this geometry type
  // --------------------------------
  public function length() { return NULL; }
  
}

