<?php
/**
 * MultiLineString: A collection of LineStrings
 */
class MultiLineString extends Collection
{
  protected $geom_type = 'MultiLineString';

  // MultiLineString is closed if all it's components are closed
  public function isClosed() {
    foreach ($this->components as $line) {
      if (!$line->isClosed()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  // Return the first Point of the first LineString.
  public function startPoint() {
    return $this->components[0]->components[0];
  }

  // Return the last Point of the last LineString.
  public function endPoint() {
    return end(end($this->components)->components);
  }

  public function simplify($tolerance = 0.5, $preserveTopology = TRUE) {
    $components = array();
    foreach ($this->components as $id => $line) {
      $startPoint = $line->startPoint();
      $endPoint = $line->endPoint();

      $startPoint->include=TRUE;
      $endPoint->include=TRUE;
      $components[] = $line->simplify($tolerance, $preserveTopology);
    }
    return new MultiLineString($components);
  }
}

