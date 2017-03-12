<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * Geometry abstract class
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

    /* Not implemented yet */
    const CIRCULAR_STRING = 'CircularString';
    const COMPOUND_CURVE = 'CompoundCurve';
    const CURVE_POLYGON = 'CurvePolygon';
    const MULTI_CURVE = 'MultiCurve';
    const MULTI_SURFACE = 'MultiSurface';
    const CURVE = 'Curve';
    const SURFACE = 'Surface';
    const POLYHEDRAL_SURFACE = 'PolyhedralSurface';
    const TIN = 'TIN';
    const TRIANGLE = 'Triangle';

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


    abstract public function geometryType();

    // Abstract: Standard
    // -----------------
    abstract public function area();

    abstract public function boundary();

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

    /**
     * check if Geometry has Z (altitude) coordinate
     *
     * @return true or false depending on point has Z value
     */
    abstract public function hasZ();

    /**
     * check if Geometry has a measure value
     *
     * @return true if is a measured value
     */
    abstract public function isMeasured();

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

    abstract public function dimension();

    abstract public function distance($geom);

    abstract public function equals($geom);

    /**
     * @return boolean
     */
    abstract public function isEmpty();

    abstract public function isSimple();


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

	abstract public function zRange();

	abstract public function zDifference();

	abstract public function elevationGain($vertical_tolerance);

	abstract public function elevationLoss($vertical_tolerance);


    // Public: Standard -- Common to all geometries
    // ----------------------------------------------------------

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
        return null;
    }

    public function equalsExact(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->equalsExact($geometry->getGeos());
        }
        return null;
    }

    /**
     * @param Geometry $geometry
     * @param string|null $pattern
     * @return string|null
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
        return null;
    }

    public function checkValidity() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->checkValidity();
        }
        return null;
    }

    public function buffer($distance) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->buffer($distance));
        }
        return null;
    }

    public function intersection(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->intersection($geometry->getGeos()));
        }
        return null;
    }

    public function convexHull() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->convexHull());
        }
        return null;
    }

    public function difference(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->difference($geometry->getGeos()));
        }
        return null;
    }

    public function symDifference(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->symDifference($geometry->getGeos()));
        }
        return null;
    }

	/**
	 * Can pass in a geometry or an array of geometries
	 * @param Geometry $geometry
	 * @return bool|mixed|null|GeometryCollection
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
        return null;
    }

    public function simplify($tolerance, $preserveTopology = false) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->simplify($tolerance, $preserveTopology));
        }
        return null;
    }

    public function disjoint(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->disjoint($geometry->getGeos());
        }
        return null;
    }

    public function touches(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->touches($geometry->getGeos());
        }
        return null;
    }

    public function intersects(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->intersects($geometry->getGeos());
        }
        return null;
    }

    public function crosses(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->crosses($geometry->getGeos());
        }
        return null;
    }

    public function within(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->within($geometry->getGeos());
        }
        return null;
    }

    public function contains($geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->contains($geometry->getGeos());
        }
        return null;
    }

    public function overlaps(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->overlaps($geometry->getGeos());
        }
        return null;
    }

    public function covers(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->covers($geometry->getGeos());
        }
        return null;
    }

    public function coveredBy(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->coveredBy($geometry->getGeos());
        }
        return null;
    }

    public function hausdorffDistance(Geometry $geometry) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->hausdorffDistance($geometry->getGeos());
        }
        return null;
    }

    public function project(Geometry $point, $normalized = null) {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->project($point->getGeos(), $normalized);
        }
        return null;
    }

}
