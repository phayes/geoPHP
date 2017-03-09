<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * MultiLineString: A collection of LineStrings
 *
 * @method LineString[] getComponents()
 */
class MultiLineString extends Collection {

    public function geometryType() {
        return 'MultiLineString';
    }

    public function dimension() {
        return 1;
    }

    // MultiLineString is closed if all it's components are closed
    public function isClosed() {
        foreach ($this->getComponents() as $line) {
            if (!$line->isClosed()) {
                return false;
            }
        }
        return true;
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
        $totalLength = 0;
        $components = $this->getComponents();
        foreach($components as $component) {
            if ($component->isEmpty()) {
                continue;
            }
            $componentCentroid = $component->getCentroidAndLength($componentLength);
            $x += $componentCentroid->x() * $componentLength;
            $y += $componentCentroid->y() * $componentLength;
            $totalLength += $componentLength;
        }
        if ($totalLength == 0) {
            return $this->startPoint();
        }
        return new Point($x / $totalLength, $y / $totalLength);
    }

}

