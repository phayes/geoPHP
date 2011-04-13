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
 * Polygon : a Polygon geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version    
 */
class Polygon extends Collection 
{
  protected $geom_type = 'Polygon';
  
  /**
   * Constructor
   *
   * The first linestring is the outer ring
   * The subsequent ones are holes
   * All linestrings should be linearrings
   *
   * @param array $linestrings The LineString array
   */
  public function __construct(array $linestrings) 
  {
    // the GeoJSON spec (http://geojson.org/geojson-spec.html) says nothing about linestring count. 
    // What should we do ?
    if (count($linestrings) > 0) 
    {
      parent::__construct($linestrings);
    }
    else
    {
      throw new Exception("Polygon without an exterior ring");
    }
  }
  
  public function area($exterior_only = FALSE) {
    //TODO: Calculate and subtract interior rings
    
    $exterior_ring = $this->components[0];
    $pts = $exterior_ring->getComponents();
    
    $c = count($pts);
  	if((int)$c == '0') return NULL;
  	$a = '0';
  	foreach($pts as $k => $p){
  	  $j = ($k + 1) % $c;
  		$a = $a + ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
    }
    
  	return abs(($a / 2));
  }
  
  public function centroid() {
    $exterior_ring = $this->components[0];
    $pts = $exterior_ring->getComponents();
    
  	$c = count($pts);
  	if((int)$c == '0') return NULL;
  	$cn = array('x' => '0', 'y' => '0');
  	$a = $this->area(TRUE);
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

	public function exteriorRing() {
		return $this->components[0];
	}
	
	public function numInteriorRings() {
		return $this->numGeometries()-1;
	}
  
  public function interiorRingN($n) {
		return $this->geometryN($n+1);
  }

	// Not valid for this geometry type
	// --------------------------------
	public function length() { return NULL; }
  
}

