<?php

namespace Phayes\GeoPHP\Geometry;

use Phayes\GeoPHP\Geometry\Collection;

/**
 * MultiPolygon: A collection of Polygons
 */
class MultiPolygon extends Collection
{
  protected $geom_type = 'MultiPolygon';
}
