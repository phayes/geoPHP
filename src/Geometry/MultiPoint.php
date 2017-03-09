<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * A MultiPoint is a 0-dimensional Collection.
 * The elements of a MultiPoint are restricted to Points.
 * The Points are not connected or ordered in any semantically important way.
 * A MultiPoint is simple if no two Points in the MultiPoint are equal (have identical coordinate values in X and Y).
 * Every MultiPoint is spatially equal under the definition in OGC 06-103r4 Clause 6.1.15.3 to a simple Multipoint.
 */
class MultiPoint extends Collection {

    public function numPoints() {
        return $this->numGeometries();
    }

    public function geometryType() {
        return 'MultiPoint';
    }

    public function dimension() {
        return 0;
    }

    public function isSimple() {
        // TODO: follow the specification
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
        foreach($this->getComponents() as $component) {
            $x += $component->x();
            $y += $component->y();
        }
        return new Point($x / $this->numPoints(), $y / $this->numPoints());
    }

    // Not valid for this geometry type
    // --------------------------------
    public function explode($toArray=false) { return NULL; }
}

