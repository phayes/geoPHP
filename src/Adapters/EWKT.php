<?php

namespace Phayes\GeoPHP\Adapters;

use Phayes\GeoPHP\Geometry\Geometry;

class EWKT extends WKT
{
  /**
   * Serialize geometries into an EWKT string.
   *
   * @param Geometry $geometry
   *
   * @return string The Extended-WKT string representation of the input geometries
   */
  public function write(Geometry $geometry)
  {
    $srid = $geometry->SRID();
    if ($srid) {
      $wkt = 'SRID=' . $srid . ';';
      $wkt .= $geometry->out('wkt');
      return $wkt;
    }
    else {
      return $geometry->out('wkt');
    }
  }
}
