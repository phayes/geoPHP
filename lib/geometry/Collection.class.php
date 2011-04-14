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
 * Collection : abstract class which represents a collection of components.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version
 */
abstract class Collection extends Geometry implements Iterator
{
  protected $components = array();

  /**
   * Constructor
   *
   * @param array $components The components array
   */
  public function __construct(array $components)
  {
  	if (empty($components)) {
  		throw new Exception("Cannot create empty collection");
  	}
  	
    foreach ($components as $component)
    {
      $this->add($component);
    }
  }

  private function add($component)
  {
    $this->components[] = $component;
  }

  /**
   * An accessor method which recursively calls itself to build the coordinates array
   *
   * @return array The coordinates array
   */
  public function getCoordinates()
  {
    $coordinates = array();
    foreach ($this->components as $component)
    {
      $coordinates[] = $component->getCoordinates();
    }
    return $coordinates;
  }

  /**
   * Returns Colection components
   *
   * @return array
   */
  public function getComponents()
  {
    return $this->components;
  }

  # Iterator Interface functions

  public function rewind()
  {
    reset($this->components);
  }

  public function current()
  {
    return current($this->components);
  }

  public function key()
  {
    return key($this->components);
  }

  public function next()
  {
    return next($this->components);
  }

  public function valid()
  {
    return $this->current() !== false;
  }

  // For collections, centroids and bbox are all the same
  public function centroid() {
		if ($this->geos()) {
			return geoPHP::load($this->geos()->centroid(),'wkt');
		}
  	
    // By default, the centroid of a collection is the average of x and y of all the component centroids
    $i = 0;
    
    foreach ($this->components as $component) {
      $component_centroid = $component->centroid();
      // On the first run through, set sum manually

      if ($i == 0) {
        $x_sum = $component_centroid->getX();
        $y_sum = $component_centroid->getY();
      }
      else {
        $x_sum += $component_centroid->getX();
        $y_sum += $component_centroid->getY();
      }
      $i++;
    }
    
    $x = $x_sum / $i;
    $y = $y_sum / $i;
    
    $centroid = new Point($x, $y);
    
    return $centroid;
  }
  
  public function getBBox() {
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

  // Standard - Collection Only
	public function numGeometries() {
		return count($this->components);
	}
	
	// Note that the standard is 1 based indexing
	public function geometryN($n) {
		$n = inval($n);
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
  	$dimention = 0;
  	foreach ($this->components as $component) {
  		if ($component->dimention() > $dimention) {
  			$dimention = $component->dimention();
  		}
  	}
  	return $dimention;
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

