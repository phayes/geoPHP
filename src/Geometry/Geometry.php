<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;
use geoPHP\Exception\UnsupportedMethodException;

/**
 * Geometry is the root class of the hierarchy. Geometry is an abstract (non-instantiable) class.
 *
 * OGC 06-103r4 6.1.2 specification:
 * The instantiable subclasses of Geometry defined in this Standard are restricted to
 * 0, 1 and 2-dimensional geometric objects that exist in 2, 3 or 4-dimensional coordinate space.
 *
 * Geometry values in R^2 have points with coordinate values for x and y.
 * Geometry values in R^3 have points with coordinate values for x, y and z or for x, y and m.
 * Geometry values in R^4 have points with coordinate values for x, y, z and m.
 * The interpretation of the coordinates is subject to the coordinate reference systems associated to the point.
 * All coordinates within a geometry object should be in the same coordinate reference systems.
 * Each coordinate shall be unambiguously associated to a coordinate reference system
 * either directly or through its containing geometry.
 *
 * The z coordinate of a point is typically, but not necessarily, represents altitude or elevation.
 * The m coordinate represents a measurement.
 */
abstract class Geometry {

    /**
     * Type constants
     */
    const POINT = 'Point';
    const LINE_STRING = 'LineString';
    const POLYGON = 'Polygon';
    const MULTI_POINT = 'MultiPoint';
    const MULTI_LINE_STRING = 'MultiLineString';
    const MULTI_POLYGON = 'MultiPolygon';
    const GEOMETRY_COLLECTION = 'GeometryCollection';

    const CIRCULAR_STRING = 'CircularString';
    const COMPOUND_CURVE = 'CompoundCurve';
    const CURVE_POLYGON = 'CurvePolygon';
    const MULTI_CURVE = 'MultiCurve'; //Abstract
    const MULTI_SURFACE = 'MultiSurface'; //Abstract
    const CURVE = 'Curve'; //Abstract
    const SURFACE = 'Surface'; //Abstract
    const POLYHEDRAL_SURFACE = 'PolyhedralSurface';
    const TIN = 'TIN';
    const TRIANGLE = 'Triangle';

    /**
     * @var bool True if Geometry has Z (altitude) value
     */
    protected $hasZ = false;
    /**
     * @var bool True if Geometry has M (measure) value
     */
    protected $isMeasured = false;

    /** @var int|null $srid Spatial Reference System Identifier (http://en.wikipedia.org/wiki/SRID) */
    protected $srid = null;

    /**
     * @var mixed|null Custom (meta)data
     */
    protected $data;

    /**
     * @var \GEOSGeometry|null
     */
    private $geos = null;




    /****************************************
     *  Basic methods on geometric objects  *
     ****************************************/

    /**
     * The inherent dimension of the geometric object, which must be less than or equal to the coordinate dimension.
     * In non-homogeneous collections, this will return the largest topological dimension of the contained objects.
     *
     * @return int
     */
    abstract public function dimension();

    /**
     * Returns the name of the instantiable subtype of Geometry of which the geometric object is an instantiable member.
     *
     * @return string
     */
    abstract public function geometryType();

    /**
     * Returns true if the geometric object is the empty Geometry.
     * If true, then the geometric object represents the empty point set ∅ for the coordinate space.
     *
     * @return bool
     */
    abstract public function isEmpty();

    /**
     * Returns true if the geometric object has no anomalous geometric points, such as self intersection or self tangency.
     * The description of each instantiable geometric class will include the specific conditions
     * that cause an instance of that class to be classified as not simple
     *
     * @return bool
     */
    abstract public function isSimple();

    /**
     * Returns the closure of the combinatorial boundary of the geometric object
     *
     * @return Geometry
     */
    abstract public function boundary();


    /*************************************************
     *  Methods applicable on certain geometry types *
     *************************************************/

    abstract public function area();

    /**
     * @return Point
     */
    abstract public function centroid();

    abstract public function length();

    abstract public function length3D();

    /**
     * @return float
     */
    abstract public function x();

    /**
     * @return float
     */
    abstract public function y();

    /**
     * @return float
     */
    abstract public function z();

    /**
     * @return float
     */
    abstract public function m();

    abstract public function numGeometries();

    /**
     * @param int $n One-based index.
     * @return Geometry|null The geometry, or null if not found.
     */
    abstract public function geometryN($n);

    /**
     * @return Point|null
     */
    abstract public function startPoint();

    /**
     * @return Point|null
     */
    abstract public function endPoint();

    abstract public function isRing(); // Missing dependency

    abstract public function isClosed(); // Missing dependency

    abstract public function numPoints();

    /**
     * @param int $n Nth point
     * @return Point|null
     */
    abstract public function pointN($n);

    abstract public function exteriorRing();

    abstract public function numInteriorRings();

    abstract public function interiorRingN($n);

    abstract public function distance($geom);

    abstract public function equals($geom);


    // Abstract: Non-Standard
    // ----------------------------------------------------------

    abstract public function getBBox();

    abstract public function asArray();

    /**
     * @return Point[]
     */
    abstract public function getPoints();

    abstract public function invertXY();

    /**
     * Get all line segments
     * @param bool $toArray return segments as LineString or array of start and end points. Explode(true) is faster
     *
     * @return LineString[] | Point[][]
     */
    abstract public function explode($toArray=false);

    abstract public function greatCircleLength($radius = null); //meters

    abstract public function haversineLength(); //degrees

    abstract public function flatten(); // 3D to 2D

	// Elevations statistics

	abstract public function minimumZ();

	abstract public function maximumZ();

    abstract public function minimumM();

    abstract public function maximumM();

	abstract public function zDifference();

	abstract public function elevationGain($verticalTolerance = 0);

	abstract public function elevationLoss($verticalTolerance = 0);


    // Public: Standard -- Common to all geometries
    // ----------------------------------------------------------

    /**
     * check if Geometry has Z (altitude) coordinate
     *
     * @return true or false depending on point has Z value
     */
    public function is3D() {
        return $this->hasZ;
    }

    /**
     * check if Geometry has a measure value
     *
     * @return true if is a measured value
     */
    public function isMeasured() {
        return $this->isMeasured;
    }

    public function SRID() {
        return $this->srid;
    }

    /**
     * @param int $srid Spatial Reference System Identifier
     */
    public function setSRID($srid) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            $this->getGeos()->setSRID($srid);
        }
        $this->srid = $srid;
    }

    /**
     * Adds custom data to the geometry
     *
     * @param string|array $property The name of the data or an associative array
     * @param mixed|null $value The data. Can be any type (string, integer, array, etc.)
     */
    public function setData($property, $value = null) {
        if (is_array($property)) {
            $this->data = $property;
        } else {
            $this->data[$property] = $value;
        }
    }

    /**
     * Returns the requested data by property name, or all data of the geometry
     *
     * @param string|null $property The name of the data. If omitted, all data will be returned
     * @return mixed|null The data or null if not exists
     */
    public function getData($property = null) {
        if ($property) {
            return $this->hasDataProperty($property) ? $this->data[$property] : null;
        } else {
            return $this->data;
        }
    }

    /**
     * Tells whether the geometry has data with the specified name
     * @param string $property The name of the property
     * @return bool True if the geometry has data with the specified name
     */
    public function hasDataProperty($property) {
        return array_key_exists($property, $this->data ?: []);
    }

    public function envelope() {
        if ($this->isEmpty()) {
            $type = geoPHP::CLASS_NAMESPACE . 'Geometry\\' . $this->geometryType();
            return new $type();
        }
        if ($this->geometryType() === Geometry::POINT) {
            return $this;
        }

        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->envelope());
        }

        $boundingBox = $this->getBBox();
        $points = [
                new Point($boundingBox['maxx'], $boundingBox['miny']),
                new Point($boundingBox['maxx'], $boundingBox['maxy']),
                new Point($boundingBox['minx'], $boundingBox['maxy']),
                new Point($boundingBox['minx'], $boundingBox['miny']),
                new Point($boundingBox['maxx'], $boundingBox['miny']),
        ];
        return new Polygon([new LineString($points)]);
    }

    // Public: Non-Standard -- Common to all geometries
    // ------------------------------------------------

    // $this->out($format, $other_args);
    public function out() {
        $args = func_get_args();

        $format = strtolower(array_shift($args));
        if (strstr($format, 'xdr')) {   //Big Endian WKB
            $args[] = true;
            $format = str_replace('xdr', '', $format);
        }

        $processor_type = geoPHP::CLASS_NAMESPACE . 'Adapter\\' . geoPHP::getAdapterMap()[$format];
        $processor = new $processor_type();
        array_unshift($args, $this);


        $result = call_user_func_array(array($processor, 'write'), $args);

        return $result;
    }

    public function coordinateDimension() {
        return 2 + ($this->hasZ() ? 1 : 0) + ($this->isMeasured() ? 1 : 0);
    }

    /**
     * Utility function to check if any line segments intersect
     * Derived from @source http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
     * @param Point $segment1Start
     * @param Point $segment1End
     * @param Point $segment2Start
     * @param Point $segment2End
     * @return bool
     */
    public static function segmentIntersects($segment1Start, $segment1End, $segment2Start, $segment2End) {
        $p0_x = $segment1Start->x();
        $p0_y = $segment1Start->y();
        $p1_x = $segment1End->x();
        $p1_y = $segment1End->y();
        $p2_x = $segment2Start->x();
        $p2_y = $segment2Start->y();
        $p3_x = $segment2End->x();
        $p3_y = $segment2End->y();

        $s1_x = $p1_x - $p0_x;
        $s1_y = $p1_y - $p0_y;
        $s2_x = $p3_x - $p2_x;
        $s2_y = $p3_y - $p2_y;

        $fps = (-$s2_x * $s1_y) + ($s1_x * $s2_y);
        $fpt = (-$s2_x * $s1_y) + ($s1_x * $s2_y);

        if ($fps == 0 || $fpt == 0) {
            return false;
        }

        $s = (-$s1_y * ($p0_x - $p2_x) + $s1_x * ($p0_y - $p2_y)) / $fps;
        $t = ($s2_x * ($p0_y - $p2_y) - $s2_y * ($p0_x - $p2_x)) / $fpt;

        // Return true if collision is detected
        return ($s > 0 && $s < 1 && $t > 0 && $t < 1);
    }


    // Public: Aliases
    // ------------------------------------------------

    public function hasZ() {
        return $this->is3D();
    }
    public function getX() {
        return $this->x();
    }
    public function getY() {
        return $this->y();
    }
    public function getZ() {
        return $this->z();
    }
    public function getM() {
        return $this->m();
    }
    public function getBoundingBox() {
        return $this->getBBox();
    }
    public function getCentroid() {
        return $this->centroid();
    }
    public function getArea() {
        return $this->area();
    }
    public function geos() {
        return $this->getGeos();
    }
    public function getGeomType() {
        return $this->geometryType();
    }
    public function getSRID() {
        return $this->SRID();
    }
    public function asText() {
        return $this->out('wkt');
    }
    public function asBinary() {
        return $this->out('wkb');
    }

    // Public: GEOS Only Functions
    // ------------------------------------------------

    /**
     * Returns the GEOS representation of Geometry if GEOS is installed
     *
     * @return \GEOSGeometry|false
     */
    public function getGeos() {
        // If it's already been set, just return it
        if ($this->geos && geoPHP::geosInstalled()) {
            return $this->geos;
        }
        // It hasn't been set yet, generate it
        if (geoPHP::geosInstalled()) {
			/** @noinspection PhpUndefinedClassInspection */
            $reader = new \GEOSWKBReader();
			/** @noinspection PhpUndefinedMethodInspection */
            $this->geos = $reader->read($this->out('wkb'));
        } else {
            $this->geos = false;
        }
        return $this->geos;
    }

    public function setGeos($geos) {
        $this->geos = $geos;
    }

    public function pointOnSurface() {
        if ($this->isEmpty()) {
            return new Point();
        }
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->pointOnSurface());
        }
        // help for implementation: http://gis.stackexchange.com/questions/76498/how-is-st-pointonsurface-calculated
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function equalsExact(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->equalsExact($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @param string|null $pattern
     * @return string|null
     * @throws UnsupportedMethodException
     */
    public function relate(Geometry $geometry, $pattern = null) {
        if ($this->getGeos()) {
            if ($pattern) {
				/** @noinspection PhpUndefinedMethodInspection */
                return $this->getGeos()->relate($geometry->getGeos(), $pattern);
            } else {
				/** @noinspection PhpUndefinedMethodInspection */
                return $this->getGeos()->relate($geometry->getGeos());
            }
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function checkValidity() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->checkValidity();
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function buffer($distance) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->buffer($distance));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function intersection(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->intersection($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function convexHull() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->convexHull());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function difference(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->difference($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function symDifference(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->symDifference($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

	/**
	 * Can pass in a geometry or an array of geometries
	 * @param Geometry $geometry
	 * @return bool|mixed|null|GeometryCollection
     * @throws UnsupportedMethodException
	 */
    public function union(Geometry $geometry) {
        if ($this->getGeos()) {
            if (is_array($geometry)) {
                $geom = $this->getGeos();
                foreach ($geometry as $item) {
					/** @noinspection PhpUndefinedMethodInspection */
                    $geom = $geom->union($item->geos());
                }
                return geoPHP::geosToGeometry($geom);
            } else {
				/** @noinspection PhpUndefinedMethodInspection */
                return geoPHP::geosToGeometry($this->getGeos()->union($geometry->getGeos()));
            }
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function simplify($tolerance, $preserveTopology = false) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->simplify($tolerance, $preserveTopology));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function disjoint(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->disjoint($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function touches(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->touches($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function intersects(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->intersects($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function crosses(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->crosses($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function within(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->within($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function contains($geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->contains($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function overlaps(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->overlaps($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function covers(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->covers($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function coveredBy(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->coveredBy($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function hausdorffDistance(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->hausdorffDistance($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    public function project(Geometry $point, $normalized = null) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->project($point->getGeos(), $normalized);
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

}
