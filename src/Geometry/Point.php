<?php

namespace geoPHP\Geometry;

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
     * @param int|float $x The x coordinate (or longitude)
     * @param int|float $y The y coordinate (or latitude)
     * @param int|float|null $z The z coordinate (or altitude) - optional
     * @param int|float|null $m measure - optional
     * @throws \Exception
     */
    public function __construct($x = null, $y = null, $z = null, $m = NULL) {
        // If X or Y is null than it is an empty point
        if ($x === null || $y === null) {
            return;
        }
        // Basic validation on x and y
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new \Exception("Cannot construct Point. x and y should be numeric");
        }

        // Convert to float in case they are passed in as a string or integer etc.
        $this->_x = floatval($x);
        $this->_y = floatval($y);

        // Check to see if this point has a Z (height) value
        if ($z !== null) {
            if (!is_numeric($z)) {
                throw new \Exception("Cannot construct Point. z should be numeric");
            }
            $this->_z = floatval($z);
        }

        // Check to see if this is a measure
        if ($m !== null) {
            if (!is_numeric($m)) {
                throw new \Exception("Cannot construct Point. m should be numeric");
            }
            $this->_m = floatval($m);
        }
    }

    public function geometryType() {
        return Geometry::POINT;
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
     * Check if point has Z (altitude) coordinate
     *
     * @return true or false depending on point has Z value
     */
    public function hasZ()  {
        return $this->_z !== null;
    }

    /**
     * Check if point has a measure value
     *
     * @return true if is a measured value
     */
    public function isMeasured()  {
        return $this->_m !== null;
    }

    /**
     * Inverts x and y coordinates
     * Useful with old applications still using lng lat
     *
     * @return self
     * */
    public function invertXY() {
        $x=$this->_x;
        $this->_x=$this->_y;
        $this->_y=$x;
        $this->setGeos(null);
        return $this;
    }


    // A point's centroid is itself
    public function centroid() {
        return $this;
    }

    public function getBBox() {
        return array(
                'maxy' => $this->y(),
                'miny' => $this->y(),
                'maxx' => $this->x(),
                'minx' => $this->x(),
        );
    }

    public function asArray() {
        if ($this->isEmpty()) {
            return [NAN, NAN];
        }
        if (!$this->hasZ() && !$this->isMeasured()) {
            return [$this->_x, $this->_y];
        }
        if ($this->hasZ() && $this->isMeasured()) {
            return [$this->_x, $this->_y, $this->_z, $this->_m];
        }
        if ($this->hasZ()) {
            return [$this->_x, $this->_y, $this->_z];
        }
        // if ($this->isMeasured())
        return [$this->_x, $this->_y, null, $this->_m];
    }

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

	public function minimumZ() {
		return $this->hasZ() ? $this->z() : null;
	}

	public function maximumZ() {
		return $this->hasZ() ? $this->z() : null;
	}

	public function zDifference() {
		return 0;
	}

	public function zRange() {
		return 0;
	}

	public function elevationGain($vertical_tolerance) {
		return 0;
	}

	public function elevationLoss($vertical_tolerance) {
		return 0;
	}

    public function minimumM() {
        return $this->isMeasured() ? $this->m() : null;
    }

    public function maximumM() {
        return $this->isMeasured() ? $this->m() : null;
    }

    // The boundary of a point is itself
    public function boundary() {
        return $this;
    }

    public function dimension() {
        return 0;
    }

    public function isEmpty() {
        return $this->_x === null || $this->_y === null;
    }

    public function numPoints() {
        return 1;
    }

    public function getPoints() {
        return array($this);
    }

    /**
     * @param Geometry $geometry
     * TODO: Z and M?
     * @return boolean
     */
    public function equals($geometry) {
        /**
         * @see: http://php.net/manual/en/function.bccomp.php
         * @see: http://php.net/manual/en/language.types.float.php
         * @see: http://tubalmartin.github.io/spherical-geometry-php/#LatLng
         */
        return (abs($this->x() - $geometry->x()) <= 1.0E-9 && abs($this->y() - $geometry->y()) <= 1.0E-9);
    }

    public function isSimple() {
        return true;
    }

    public function flatten() {
        if ( $this->hasZ() || $this->isMeasured() ) {
            return new Point($this->x(), $this->y());
        } else {
            return $this;
        }
    }

    /**
     * @param Geometry|Collection $geometry
     * @return float|int|null
     */
    public function distance($geometry) {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
        }
        if ($geometry->geometryType() == Geometry::POINT) {
            return sqrt(pow(($this->x() - $geometry->x()), 2) + pow(($this->y() - $geometry->y()), 2));
        }
        if ($geometry->isEmpty()) return NULL;
        if ($geometry->geometryType() == Geometry::MULTI_POINT || $geometry->geometryType() == Geometry::GEOMETRY_COLLECTION) {
            $distance = NULL;
            foreach ($geometry->getComponents() as $component) {
                $check_distance = $this->distance($component);
                if ($check_distance === 0) return 0;
                if ($check_distance === NULL) return NULL;
                if ($distance === NULL) $distance = $check_distance;
                if ($check_distance < $distance) $distance = $check_distance;
            }
            return $distance;
        }
        else {
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
                if ($distance === 0.0) return 0;
            }
            return $distance;
        }
    }

    /* The following methods are not valid for this geometry type */
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

