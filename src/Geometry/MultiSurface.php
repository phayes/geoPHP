<?php

namespace geoPHP\Geometry;

/**
 * Class MultiSurface
 * TODO write this
 *
 * @package geoPHP\Geometry
 */
abstract class MultiSurface extends MultiGeometry {

    public function geometryType() {
        return Geometry::MULTI_SURFACE;
    }

    public function dimension() {
        return 2;
    }
}

