<?php

namespace geoPHP\Geometry;

/**
 * Class Curve
 * TODO write this
 *
 * @package geoPHP\Geometry
 */
abstract class Curve extends Collection {

    /**
     * @var Point[] A curve consists of sequence of Points
     */
    protected $components = [];

    protected $startPoint = null;
    protected $endPoint = null;

    public function geometryType() {
        return Geometry::CURVE;
    }

    public function dimension() {
        return 1;
    }

    public function startPoint() {
        if (!isset($this->startPoint)) {
            $this->startPoint = $this->pointN(1);
        }
        return $this->startPoint;
    }

    public function endPoint() {
        if (!isset($this->endPoint)) {
            $this->endPoint = $this->pointN($this->numPoints());
        }
        return $this->endPoint;
    }

    public function isClosed() {
        return ($this->startPoint() && $this->endPoint() ? $this->startPoint()->equals($this->endPoint()) : false);
    }

    public function isRing() {
        return ($this->isClosed() && $this->isSimple());
    }

    /**
     * The boundary of a non-closed Curve consists of its end Points
     *
     * @return LineString|MultiPoint
     */
    public function boundary() {
        if ($this->isEmpty()) {
            return new LineString();
        } else {
            if ($this->isClosed()) {
                return new MultiPoint();
            } else {
                return new MultiPoint([$this->startPoint(), $this->endPoint()]);
            }
        }
    }

    // Not valid for this geometry type
    // --------------------------------
    public function area() {
        return 0;
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

