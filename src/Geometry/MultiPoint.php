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
class MultiPoint extends MultiGeometry {

    /**
     * @var Point[] $components The elements of a MultiPoint are Points
     */

    /**
     * @return string
     */
    public function geometryType() {
        return Geometry::MULTI_POINT;
    }

    /**
     * MultiPoint is 0-dimensional
     * @return int 0
     */
    public function dimension() {
        return 0;
    }

    public static function fromArray($array) {
        $points = [];
        foreach ($array as $point) {
            $points[] = Point::fromArray($point);
        }
        return new static($points);
    }

    /**
     * A MultiPoint is simple if no two Points in the MultiPoint are equal (have identical coordinate values in X and Y).
     * @return bool
     */
    public function isSimple() {
        $componentCount = count($this->components);
        for ($i=0; $i < $componentCount ; $i++) {
            for ($j=$i+1; $j < $componentCount ; $j++) {
                if ($this->components[$i]->equals($this->components[$j])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * The boundary of a MultiPoint is the empty set.
     * @return GeometryCollection
     */
    public function boundary() {
        return new GeometryCollection();
    }

    public function numPoints() {
        return $this->numGeometries();
    }

    public function centroid() {
        if ($this->isEmpty()) {
            return new Point();
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
    public function explode($toArray=false) {
        return null;
    }
}

