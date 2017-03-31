<?php

namespace geoPHP\Geometry;
use geoPHP\geoPHP;

/**
 * MultiGeometry is an abstract collection of geometries
 *
 * @package geoPHP\Geometry
 */
abstract class MultiGeometry extends Collection {


    /**
     * @return bool|null
     */
    public function isSimple() {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
        }

        // A collection is simple if all it's components are simple
        foreach ($this->components as $component) {
            if (!$component->isSimple()) {
                return false;
            }
        }

        return true;
    }

    // By default, the boundary of a collection is the boundary of it's components
    public function boundary() {
        if ($this->isEmpty()) {
            return new LineString();
        }

        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->boundary();
        }

        $components_boundaries = array();
        foreach ($this->components as $component) {
            $components_boundaries[] = $component->boundary();
        }
        return geoPHP::buildGeometry($components_boundaries);
    }

    public function area() {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->area();
        }

        $area = 0;
        foreach ($this->components as $component) {
            $area += $component->area();
        }
        return $area;
    }

    /**
     *  Returns the length of this Collection in its associated spatial reference.
     * Eg. if Geometry is in geographical coordinate system it returns the length in degrees
     * @return float|int
     */
    public function length() {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length();
        }
        return $length;
    }

    public function length3D() {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length3D();
        }
        return $length;
    }

    /**
     * Returns the degree based Geometry' length in meters
     * @param int $radius default is the semi-major axis of WGS84
     * @return int the length in meters
     */
    public function greatCircleLength($radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS) {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->greatCircleLength($radius);
        }
        return $length;
    }

    public function haversineLength() {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->haversineLength();
        }
        return $length;
    }

    public function minimumZ() {
        $min = PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMin = $component->minimumZ();
            if ($componentMin < $min) {
                $min = $componentMin;
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumZ() {
        $max = ~PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMax = $component->maximumZ();
            if ($componentMax > $max) {
                $max = $componentMax;
            }
        }
        return $max > ~PHP_INT_MAX ? $max : null;
    }

    public function zDifference() {
        $startPoint = $this->startPoint();
        $endPoint = $this->endPoint();
        if ($startPoint && $endPoint && $startPoint->hasZ() && $endPoint->hasZ()) {
            return abs($startPoint->z() - $endPoint->z());
        } else {
            return null;
        }
    }

    public function elevationGain($vertical_tolerance = 0) {
        $gain = null;
        foreach ($this->components as $component) {
            $gain += $component->elevationGain($vertical_tolerance);
        }
        return $gain;
    }

    public function elevationLoss($vertical_tolerance = 0) {
        $loss = null;
        foreach ($this->components as $component) {
            $loss += $component->elevationLoss($vertical_tolerance);
        }
        return $loss;
    }

    public function minimumM() {
        $min = PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMin = $component->minimumM();
            if ($componentMin < $min) {
                $min = $componentMin;
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumM() {
        $max = ~PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMax = $component->maximumM();
            if ($componentMax > $max) {
                $max = $componentMax;
            }
        }
        return $max > ~PHP_INT_MAX ? $max : null;
    }



    public function startPoint() {
        return null;
    }

    public function endPoint() {
        return null;
    }

    public function isRing() {
        return null;
    }

    public function isClosed() {
        return null;
    }

    public function pointN($n) {
        return null;
    }

    public function exteriorRing() {
        return null;
    }

    public function numInteriorRings() {
        return null;
    }

    public function interiorRingN($n) {
        return null;
    }
}

