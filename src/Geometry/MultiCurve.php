<?php

namespace geoPHP\Geometry;

/**
 * Class MultiCurve
 * TODO write this
 *
 * @package geoPHP\Geometry
 */
abstract class MultiCurve extends MultiGeometry {

    public function geometryType() {
        return Geometry::MULTI_CURVE;
    }

    public function dimension() {
        return 1;
    }

    /**
     * MultiCurve is closed if all it's components are closed
     *
     * @return bool
     */
    public function isClosed() {
        foreach ($this->getComponents() as $line) {
            if (!$line->isClosed()) {
                return false;
            }
        }
        return true;
    }
}

