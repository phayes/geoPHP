<?php
/**
 * WKT (Well Known Text) Adapter
 */
class WKT extends GeoAdapter {
  
  protected $hasZ      = false;
  protected $measured  = false;
   
  /**
   * Read WKT string into geometry objects
   *
   * @param string $WKT A WKT string
   *
   * @return Geometry
   */
  public function read($wkt) {
  	$this->hasZ      = false;
  	$this->measured  = false;
  	
    $wkt = trim($wkt);    
    $srid = NULL;
    
    // If it contains a ';', then it contains additional SRID data
    if (strpos($wkt,';')) {
      $parts = explode(';', $wkt);
    	$wkt = $parts[1];
    	$eparts = explode('=',$parts[0]);
    	$srid = $eparts[1];
    } 
    
    // If geos is installed, then we take a shortcut and let it parse the WKT
    if ( geoPHP::geosInstalled() ) {
      $reader = new GEOSWKTReader();      
      $geom = geoPHP::geosToGeometry($reader->read($wkt));
      if ($srid) $geom->setSRID($srid);
      return $geom;      
    }
       
    if (  $geom = $this->parseType($wkt) ) {   		
    	if ($srid) $geom->setSRID($srid);
    	return $geom;
    } 
    throw new Exception('Invalid Wkt');    
    
  }
  
  private function parseType($wkt) {
	  // geometry type is the first word
	  if ( preg_match('#^([a-z]*)#i', $wkt, $m) ) {
	  	$geotype = strtolower($m[1]);
	  	$geoTypeList = geoPHP::geometryList();
	  	if( array_key_exists($geotype, $geoTypeList) ) {
	  		$data_string = $this->getDataString($wkt, $geotype);
	  		$this->hasZ 	= $data_string[0];
	  		$this->measured = $data_string[1];
	  		$method = 'parse'.$geotype;
	  		$geom = $this->$method($data_string[2]);
	  		$geom->set3d($this->hasZ);
	  		$geom->setMeasured($this->measured);
	  		return $geom;
	  	}
	  }
  }
  
  private function parsePoint($data_string) {
    $parts = explode(' ', trim($data_string));
    $z = $m = null;
    if ( $this->hasZ ) $z = $parts[2];
    if ( $this->measured ) $m = ( $this->hasZ ) ? $parts[3] :  $parts[2];
    return new Point($parts[0], $parts[1], $z, $m);
  }

  private function parseLineString($data_string) {
    // If it's marked as empty, then return an empty line
    if ($data_string == 'EMPTY') return new LineString();
    
    $parts = explode(',',$data_string);
    $points = array();
    foreach ($parts as $part) {
      $points[] = $this->parsePoint($part);
    }
    return new LineString($points);
  }

  private function parsePolygon($data_string) {   
    // If it's marked as empty, then return an empty polygon
    if ($data_string == 'EMPTY') return new Polygon();
    
    $lines = array();
    if ( preg_match_all('/\(([^)(]*)\)/', $data_string, $m) ) {
    	$parts = $m[1];    	
    	foreach ($parts as $part) {
    		$lines[] = $this->parseLineString($part);
    	}
    }   
    return new Polygon($lines);
  }

  private function parseMultiPoint($data_string) {
    // If it's marked as empty, then return an empty MutiPoint
    if ($data_string == 'EMPTY') return new MultiPoint();

    $points = array();
    if (  preg_match_all('/\((.*?)\)/', $data_string, $m) ) {
    	$parts = $m[1];
    	foreach ($parts as $part) {
    		$points[] =  $this->parsePoint($part);
    	}
    }
    return new MultiPoint($points);
  }
  
  private function parseMultiLineString($data_string) {
    // If it's marked as empty, then return an empty multi-linestring
    if ($data_string == 'EMPTY') return new MultiLineString();    
    $lines = array();
    if (  preg_match_all('/\(([^\(].*?)\)/', $data_string, $m) ) {
    	$parts = $m[1];
    	foreach ($parts as $part) {
    		$lines[] =  $this->parseLineString($part);
    	}
    }
    return new MultiLineString($lines);
  }

  private function parseMultiPolygon($data_string) {
    // If it's marked as empty, then return an empty multi-polygon
    if ($data_string == 'EMPTY') return new MultiPolygon();
    
    $polygons = array();
    if (  preg_match_all('/\(\(.*?\)\)/', $data_string, $m) ) {
    	$parts = $m[0];
    	foreach ($parts as $part) {
    		$polygons[] =  $this->parsePolygon($part);
    	}
    }
    return new MultiPolygon($polygons);
  }
 
  private function parseGeometryCollection($data_string) {
     // If it's marked as empty, then return an empty geom-collection
    if ($data_string == 'EMPTY') return new GeometryCollection();
    
    $geometries = array();
    // do not cut ZM 
    $str = preg_replace('/([a-z]{3,})/i', '|$1', $data_string);    
    $components = explode('|', substr($str,1) );

    foreach ($components as $component) {   
      $geometries[] = $this->parseType(trim($component,', '));
    }
    return new GeometryCollection($geometries);
  }

  protected function getDataString($wkt, $type) {
    // data is between () or is empty  	
    if ( preg_match('#(z{0,1})(m{0,1})[\s]*\((.*)\)$#i', trim($wkt), $m) ) {    	
  		return array($m[1], $m[2], $m[3]);
  	}
  	return 'EMPTY';
  }

    
  /**
   * Serialize geometries into a WKT string.
   *
   * @param Geometry $geometry
   *
   * @return string The WKT string representation of the input geometries
   */
 public function write(Geometry $geometry) {
    // If geos is installed, then we take a shortcut and let it write the WKT
    if (geoPHP::geosInstalled()) {
      $writer = new GEOSWKTWriter();
      $writer->setTrim(TRUE);
      return $writer->write($geometry->geos());
    }
    $this->measured = $geometry->isMeasured();
    $this->hasZ     = $geometry->hasZ();
        
    if ($geometry->isEmpty()) return strtoupper($geometry->geometryType()).' EMPTY';
    
    if ($data = $this->extractData($geometry, $geometry)) {
      $p='';
      if(  $this->hasZ ) 	 $p .= 'Z';
      if ( $this->measured ) $p .= 'M';
      return strtoupper($geometry->geometryType()).' '.$p.' ('.$data.')';
    }
  }
  
  /**
   * Extract geometry to a WKT string
   *
   * @param Geometry $geometry A Geometry object
   *
   * @return string
   */
   public function extractData($geometry) {
    $parts = array();
    switch ($geometry->geometryType()) {
      case 'Point':
        $p = $geometry->getX().' '.$geometry->getY();
        if ( $this->hasZ )    	$p .= ' '.$geometry->z();
        if ( $this->measured ) 	$p .= ' '.$geometry->m();
        return $p;
      case 'LineString':
        foreach ($geometry->getComponents() as $component) {
          $parts[] = $this->extractData($component);
        }
        return implode(', ', $parts);
      case 'Polygon':
      case 'MultiPoint':
      case 'MultiLineString':
      case 'MultiPolygon':
        foreach ($geometry->getComponents() as $component) {
          $parts[] = '('.$this->extractData($component).')';
        }
        return implode(', ', $parts);
      case 'GeometryCollection':
        foreach ($geometry->getComponents() as $component) {
          $this->hasZ = $component->hasZ();
          $this->measured = $component->isMeasured();
          $geometry->set3d($this->hasZ);
          $geometry->setMeasured($this->measured);
          
          $p='';
          if(  $this->hasZ ) 	 $p .= 'Z';
          if ( $this->measured ) $p .= 'M';          
          $parts[] = strtoupper($component->geometryType()).' '.$p.' ('.$this->extractData($component).')';
        }
        return implode(', ', $parts);
    }
  }
}
