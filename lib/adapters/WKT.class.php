<?php
/**
 * WKT (Well Known Text) Adapter
 */
class WKT extends GeoAdapter
{
  
  /**
   * Read WKT string into geometry objects
   *
   * @param string $WKT A WKT string
   *
   * @return Geometry
   */
  public function read($wkt) {
    $wkt = trim($wkt);
    
    // If geos is installed, then we take a shortcut and let it parse the WKT
    if (geoPHP::geosInstalled()) {
      $reader = new GEOSWKTReader();
      return geoPHP::geosToGeometry($reader->read($wkt));
    }
    $wkt = str_replace(', ', ',', $wkt);
    
    // For each geometry type, check to see if we have a match at the
    // beggining of the string. If we do, then parse using that type
    foreach (geoPHP::geometryList() as $geom_type) {
      $wkt_geom = strtoupper($geom_type);
      if (strtoupper(substr($wkt, 0, strlen($wkt_geom))) == $wkt_geom) {
        $data_string = $this->getDataString($wkt, $wkt_geom);
        $method = 'parse'.$geom_type;
        return $this->$method($data_string);
      }
    }
  }
  
  private function parsePoint($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode(' ',$data_string);
    return new Point($parts[0], $parts[1]);
  }

  private function parseLineString($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode(',',$data_string);
    $points = array();
    foreach ($parts as $part) {
      $points[] = $this->parsePoint($part);
    }
    return new LineString($points);
  }

  private function parsePolygon($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode('),(',$data_string);
    $lines = array();
    foreach ($parts as $part) {
      if (!$this->beginsWith($part,'(')) $part = '(' . $part;
      if (!$this->endsWith($part,')'))   $part = $part . ')';
      $lines[] = $this->parseLineString($part);
    }
    return new Polygon($lines);
  }

  private function parseMultiPoint($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode(',',$data_string);
    $points = array();
    foreach ($parts as $part) {
      $points[] = $this->parsePoint($part);
    }
    return new MultiPoint($points);
  }
  
  private function parseMultiLineString($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode('),(',$data_string);
    $lines = array();
    foreach ($parts as $part) {
      // Repair the string if the explode broke it
      if (!$this->beginsWith($part,'(')) $part = '(' . $part;
      if (!$this->endsWith($part,')'))   $part = $part . ')';
      $lines[] = $this->parseLineString($part);
    }
    return new MultiLineString($lines);
  }

  private function parseMultiPolygon($data_string) {
    $data_string = $this->trimParens($data_string);
    $parts = explode(')),((',$data_string);
    $polys = array();
    foreach ($parts as $part) {
      // Repair the string if the explode broke it
      if (!$this->beginsWith($part,'((')) $part = '((' . $part;
      if (!$this->endsWith($part,'))'))   $part = $part . '))';
      $polys[] = $this->parsePolygon($part);
    }
    return new MultiPolygon($polys);
  }

  private function parseGeometryCollection($data_string) {
    $data_string = $this->trimParens($data_string);
    $geometries = array();
    $matches = array();
    $str = preg_replace('/,\s*([A-Za-z])/', '|$1', $data_string);
    $components = explode('|', trim($str));
    
    foreach ($components as $component) {
      $geometries[] = $this->read($component);
    }
    return new GeometryCollection($geometries);
  }

  protected function getDataString($wkt, $type) {
    return substr($wkt, strlen($type));
  }
  
  /**
   * Trim the parenthesis and spaces
   */
  protected function trimParens($str) {
    $str = trim($str);
    
    // We want to only strip off one set of parenthesis
    if ($this->beginsWith($str, '(')) {
      return substr($str,1,-1);
    }
    else return $str;
  }
  
  protected function beginsWith($str, $char) {
    if (substr($str,0,strlen($char)) == $char) return TRUE;
    else return FALSE;
  }

  protected function endsWith($str, $char) {
    if (substr($str,(0 - strlen($char))) == $char) return TRUE;
    else return FALSE;
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
      return $writer->write($geometry->geos());
    }
    
    if ($data = $this->extractData($geometry)) {
      return strtoupper($geometry->geometryType()).' ('.$data.')';
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
        return $geometry->getX().' '.$geometry->getY();
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
          $parts[] = strtoupper($component->geometryType()).' ('.$this->extractData($component).')';
        }
        return implode(', ', $parts);
    }
  }
}
