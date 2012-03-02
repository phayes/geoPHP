<?php
/**
 * MultiLineString: A collection of LineStrings   
 */
class MultiLineString extends Collection 
{
  protected $geom_type = 'MultiLineString';
  
  // Length of a MultiLineString is the sum of it's components
  public function length() {
    if ($this->geos()) {
      return $this->geos()->length();
    }
    
    $length = 0;
    foreach ($this->components as $line) {
      $length += $line->length();
    }
    return $length;
  }
  
  // MultiLineString is closed if all it's components are closed
  public function isClosed() {
    foreach ($this->components as $line) {
      if (!$line->isClosed()) {
        return FALSE;
      }
    }
    return TRUE;
  }
  
}

