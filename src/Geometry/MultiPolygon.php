<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * MultiPolygon: A collection of Polygons
 *
 * @method Polygon[] getComponents()
 */
class MultiPolygon extends Collection {

    public function geometryType() {
        return 'MultiPolygon';
    }

    public function dimension() {
        return 2;
    }

    public function centroid() {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->centroid());
        }

        $x = 0;
        $y = 0;
        $totalArea = 0;
        $components = $this->getComponents();
        foreach($components as $component) {
            if ($component->isEmpty()) {
                continue;
            }
            $componentArea = $component->area();
            $totalArea += $componentArea;
            $componentCentroid = $component->centroid();
            $x += $componentCentroid->x() * $componentArea;
            $y += $componentCentroid->y() * $componentArea;
        }
        return new Point($x / $totalArea, $y / $totalArea);
    }
}
