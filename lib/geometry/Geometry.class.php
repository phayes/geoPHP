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
	abstract public function pointOnSurface();    // Polygon --> not done
  /**
  //@@TODO: UNFISHED (NOT YET IMPLEMENDTED)
	abstract public function equals();
	abstract public function equalsExact();
  abstract public function relate();
	abstract public function relateBoundaryNodeRule(); 
	abstract public function isEmpty();
	abstract public function checkValidity();
	abstract public function isSimple();
	abstract public function typeName();
	abstract public function typeId();
	
	abstract public function project();
	abstract public function interpolate();
	abstract public function buffer();
	abstract public function intersection();
	abstract public function convexHull();
	abstract public function difference();
	abstract public function symDifference();
	abstract public function union(); // also does union cascaded
	abstract public function simplify(); // also does topology-preserving
	abstract public function extractUniquePoints(); 
	abstract public function disjoint();
	abstract public function touches();
	abstract public function intersects();
	abstract public function crosses();
	abstract public function within();
	abstract public function contains();
	abstract public function overlaps();
	abstract public function covers();
	abstract public function coveredBy();
	abstract public function numCoordinates();
	abstract public function dimension();
	abstract public function coordinateDimension();
	abstract public function distance();
	abstract public function hausdorffDistance();
	abstract public function snapTo();

	*/
  
  // Abtract: Non-Standard
  // ---------------------
  abstract public function getCoordinates();
  abstract public function getBBox();


  // Public: Standard -- Common to all geometries
  // --------------------------------------------
  public function getSRID() {
  	return $this->srid;
  }
  
	public function setSRID($srid) {
		$this->srid = $srid;
	}
	
  public function hasZ() {
	  // geoPHP does not support Z values at the moment
	  return FALSE;	
	}
  
  public function envelope() {
  	$bbox = $this->getBBox();
  	$points = array (
  	  new Point($bbox['maxy'],$bbox['minx']),
  	  new Point($bbox['maxy'],$bbox['maxx']),
  	  new Point($bbox['miny'],$bbox['maxx']),
  	  new Point($bbox['miny'],$bbox['minx']),
  	  new Point($bbox['maxy'],$bbox['minx']),
  	);
  	$outer_boundary = new LinearRing($points);
  	return new Polygon($outer_boundary);
  }

  
  // Public: Non-Standard -- Common to all geometries
  // ------------------------------------------------
  public function getGeomType() {
    return $this->geom_type;
  }

  public function getGeoInterface() {
    return array(
      'type'=> $this->getGeomType(),
      'coordinates'=> $this->getCoordinates()
    );
  }
  
  public function out($format) {
    $type_map = geoPHP::getAdapterMap();
    $processor_type = $type_map[$format];
    $processor = new $processor_type();
    
    return $processor->write($this);
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

}
