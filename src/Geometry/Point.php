<?php

namespace geoPHP\Geometry;
use geoPHP\Exception\InvalidGeometryException;

/**
 * A Point is a 0-dimensional geometric object and represents a single location in coordinate space.
 * A Point has an x-coordinate value, a y-coordinate value.
 * If called for by the associated Spatial Reference System, it may also have coordinate values for z and m.
 */
class Point extends Geometry {

	protected $_x = null;
	protected $_y = null;
	protected $_z = null;
    protected $_m = null;

    /**
     * Constructor
     *
     * @param int|float|null $x The x coordinate (or longitude)
     * @param int|float|null $y The y coordinate (or latitude)
     * @param int|float|null $z The z coordinate (or altitude) - optional
     * @param int|float|null $m Measure - optional
     * @throws \Exception
     */
    public function __construct($x = null, $y = null, $z = null, $m = null) {
        // If X or Y is null than it is an empty point
        if ($x !== null && $y !== null) {
            // Basic validation on x and y
            if (!is_numeric($x) || !is_numeric($y)) {
                throw new InvalidGeometryException("Cannot construct Point. x and y should be numeric");
            }

            // Convert to float in case they are passed in as a string or integer etc.
            $this->_x = floatval($x);
            $this->_y = floatval($y);
        }

        // Check to see if this point has Z (height) value
        if ($z !== null) {
            if (!is_numeric($z)) {
                throw new InvalidGeometryException("Cannot construct Point. z should be numeric");
            }
            $this->hasZ = true;
            $this->_z = $this->_x !== null ? floatval($z) : null;
        }

        // Check to see if this is a measure
        if ($m !== null) {
            if (!is_numeric($m)) {
                throw new InvalidGeometryException("Cannot construct Point. m should be numeric");
            }
            $this->isMeasured = true;
            $this->_m = $this->_x !== null ? floatval($m) : null;
        }
    }

    public static function fromArray($coordinates) {
        return (new \ReflectionClass(get_called_class()))->newInstanceArgs($coordinates);
    }

    public function geometryType() {
        return Geometry::POINT;
    }

    public function dimension() {
        return 0;
    }

    /**
     * Get X (longitude) coordinate
     *
     * @return float The X coordinate
     */
    public function x() {
        return $this->_x;
    }

    /**
     * Returns Y (latitude) coordinate
     *
     * @return float The Y coordinate
     */
    public function y() {
        return $this->_y;
    }

    /**
     * Returns Z (altitude) coordinate
     *
     * @return float The Z coordinate or NULL is not a 3D point
     */
    public function z() {
        return $this->_z;
    }

    /**
     * Returns M (measured) value
     *
     * @return float The measured value
     */
    public function m() {
        return $this->_m;
    }

    /**
     * Inverts x and y coordinates
     * Useful with old applications still using lng lat
     *
     * @return self
     * */
    public function invertXY() {
        $x = $this->_x;
        $this->_x = $this->_y;
        $this->_y = $x;
        $this->setGeos(null);
        return $this;
    }

    // A point's centroid is itself
    public function centroid() {
        return $this;
    }

    public function getBBox() {
        return [
                'maxy' => $this->y(),
                'miny' => $this->y(),
                'maxx' => $this->x(),
                'minx' => $this->x(),
        ];
    }

    public function asArray() {
        if ($this->isEmpty()) {
            return [NAN, NAN];
        }
        if (!$this->hasZ && !$this->isMeasured) {
            return [$this->_x, $this->_y];
        }
        if ($this->hasZ && $this->isMeasured) {
            return [$this->_x, $this->_y, $this->_z, $this->_m];
        }
        if ($this->hasZ) {
            return [$this->_x, $this->_y, $this->_z];
        }
        // if ($this->isMeasured)
        return [$this->_x, $this->_y, null, $this->_m];
    }

    /**
     * The boundary of a MultiPoint is the empty set.
     * @return GeometryCollection
     */
    public function boundary() {
        return new GeometryCollection();
    }

    public function isEmpty() {
        return $this->_x === null;
    }

    public function numPoints() {
        return 1;
    }

    public function getPoints() {
        return [$this];
    }

    /**
     * Determines weather the specified geometry is spatially equal to this Point
     *
     * Because of limited floating point precision in PHP, equality can be only approximated
     * @see: http://php.net/manual/en/function.bccomp.php
     * @see: http://php.net/manual/en/language.types.float.php
     *
     * @param Point|Geometry $geometry
     *
     * @return boolean
     */
    public function equals($geometry) {
        if ($geometry->geometryType() === Geometry::POINT) {
            return (abs($this->x() - $geometry->x()) <= 1.0E-9 && abs($this->y() - $geometry->y()) <= 1.0E-9);
        } else {
            return false;
        }
    }

    public function isSimple() {
        return true;
    }

    public function flatten() {
        $this->_z = null;
        $this->_m = null;
        $this->hasZ = false;
        $this->isMeasured = false;
        $this->setGeos(null);
    }

    /**
     * @param Geometry|Collection $geometry
     * @return float|null
     */
    public function distance($geometry) {
        if ($this->isEmpty() || $geometry->isEmpty()) {
            return null;
        }
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
        }
        if ($geometry->geometryType() == Geometry::POINT) {
            return sqrt(pow(($this->x() - $geometry->x()), 2) + pow(($this->y() - $geometry->y()), 2));
        }
        if ($geometry->geometryType() == Geometry::MULTI_POINT || $geometry->geometryType() == Geometry::GEOMETRY_COLLECTION) {
            $distance = NULL;
            foreach ($geometry->getComponents() as $component) {
                $check_distance = $this->distance($component);
                if ($check_distance === 0) return 0.0;
                if ($check_distance === NULL) continue;
                if ($distance === NULL) $distance = $check_distance;
                if ($check_distance < $distance) $distance = $check_distance;
            }
            return $distance;
        } else {
            // For LineString, Polygons, MultiLineString and MultiPolygon. the nearest point might be a vertex,
            // but it could also be somewhere along a line-segment that makes up the geometry (between vertices).
            // Here we brute force check all line segments that make up these geometries
            $distance = NULL;
            foreach ($geometry->explode(true) as $seg) {
                // As per http://stackoverflow.com/questions/849211/shortest-distance-between-a-point-and-a-line-segment
                // and http://paulbourke.net/geometry/pointline/
                $x1 = $seg[0]->x();
                $y1 = $seg[0]->y();
                $x2 = $seg[1]->x();
                $y2 = $seg[1]->y();
                $px = $x2 - $x1;
                $py = $y2 - $y1;
                $d = ($px*$px) + ($py*$py);
                if ($d == 0) {
                    // Line-segment's endpoints are identical. This is merely a point masquerading as a line-sigment.
                    $check_distance = $this->distance($seg[1]);
                }
                else {
                    $x3 = $this->x();
                    $y3 = $this->y();
                    $u =  ((($x3 - $x1) * $px) + (($y3 - $y1) * $py)) / $d;
                    if ($u > 1) $u = 1;
                    if ($u < 0) $u = 0;
                    $x = $x1 + ($u * $px);
                    $y = $y1 + ($u * $py);
                    $dx = $x - $x3;
                    $dy = $y - $y3;
                    $check_distance = sqrt(($dx * $dx) + ($dy * $dy));
                }
                if ($distance === NULL) $distance = $check_distance;
                if ($check_distance < $distance) $distance = $check_distance;
                if ($distance === 0.0) return 0.0;
            }
            return $distance;
        }
    }

    public function minimumZ() {
        return $this->hasZ ? $this->z() : null;
    }

    public function maximumZ() {
        return $this->hasZ ? $this->z() : null;
    }

    public function minimumM() {
        return $this->isMeasured ? $this->m() : null;
    }

    public function maximumM() {
        return $this->isMeasured ? $this->m() : null;
    }

    /* The following methods are not valid for this geometry type */

    public function area() {
        return 0;
    }

    public function length() {
        return 0;
    }

    public function length3D() {
        return 0;
    }

    public function greatCircleLength($radius = null) {
        return 0;
    }

    public function haversineLength() {
        return 0;
    }

    public function zDifference() {
        return null;
    }

    public function elevationGain($vertical_tolerance) {
        return null;
    }

    public function elevationLoss($vertical_tolerance) {
        return null;
    }

    public function numGeometries() {
        return null;
    }

    public function geometryN($n) {
        return null;
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

    /**
     * @param bool|false $toArray
     * @return null
     */
    public function explode($toArray=false) {
        return null;
    }
}

