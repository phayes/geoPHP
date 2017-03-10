<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * Collection: Abstract class for compound geometries
 *
 * A geometry is a collection if it is made up of other
 * component geometries. Therefore everything but a Point
 * is a Collection. For example a LingString is a collection
 * of Points. A Polygon is a collection of LineStrings etc.
 */
abstract class Collection extends Geometry {

    /** @var Geometry[]|Collection[] */
    protected $components = [];
    protected $hasZ = false;
    protected $isMeasured = false;

    /**
     * Constructor: Checks and sets component geometries
     *
     * @param Geometry[] $components array of geometries
     * @throws \Exception
     */
    public function __construct($components = array()) {
        if (!is_array($components)) {
            throw new \Exception("Component geometries must be passed as an array");
        }
        $componentCount = count($components);
        for ($i=0; $i < $componentCount; ++$i) { // foreach is too memory-intensive here
            if ($components[$i] instanceof Geometry) {
                if ($components[$i]->isEmpty()) {
                    throw new \Exception('Cannot create a collection with empty ' . $components[$i]->getGeomType() . 's (' . ($i+1) . '. component)');
                }
                if ($components[$i]->hasZ()) {
                    $this->hasZ = true;
                }
                if ($components[$i]->isMeasured()) {
                    $this->isMeasured = true;
                }
            } else {
                throw new \Exception("Cannot create a collection with non-geometries");
            }
        }
        $this->components = $components;
    }

    /**
     * check if Geometry has Z (altitude) coordinate
     *
     * @return true or false depending on point has Z value
     */
    public function hasZ() {
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

    /**
     * Returns Collection component geometries
     *
     * @return Geometry[]
     */
    public function getComponents() {
        return $this->components;
    }

    /**
     * Inverts x and y coordinates
     * Useful for old data still using lng lat
     *
     * @return self
     *
     * */
    public function invertXY() {
        foreach ($this->components as $component) {
            $component->invertXY();
        }
        $this->setGeos(null);
        return $this;
    }

    public function getBBox() {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            $envelope = $this->getGeos()->envelope();
            /** @noinspection PhpUndefinedMethodInspection */
            if ($envelope->typeName() == 'Point') {
                return geoPHP::geosToGeometry($envelope)->getBBox();
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $geos_ring = $envelope->exteriorRing();
            /** @noinspection PhpUndefinedMethodInspection */
            return array(
                    'maxy' => $geos_ring->pointN(3)->getY(),
                    'miny' => $geos_ring->pointN(1)->getY(),
                    'maxx' => $geos_ring->pointN(1)->getX(),
                    'minx' => $geos_ring->pointN(3)->getX(),
            );
        }

        // Go through each component and get the max and min x and y
        $maxX = $maxY = $minX = $minY = 0;
        foreach ($this->components as $i=>$component) {
            $componentBoundingBox = $component->getBBox();

            // On the first run through, set the bounding box to the component's bounding box
            if ($i == 0) {
                $maxX = $componentBoundingBox['maxx'];
                $maxY = $componentBoundingBox['maxy'];
                $minX = $componentBoundingBox['minx'];
                $minY = $componentBoundingBox['miny'];
            }

            // Do a check and replace on each boundary, slowly growing the bounding box
            $maxX = $componentBoundingBox['maxx'] > $maxX ? $componentBoundingBox['maxx'] : $maxX;
            $maxY = $componentBoundingBox['maxy'] > $maxY ? $componentBoundingBox['maxy'] : $maxY;
            $minX = $componentBoundingBox['minx'] < $minX ? $componentBoundingBox['minx'] : $minX;
            $minY = $componentBoundingBox['miny'] < $minY ? $componentBoundingBox['miny'] : $minY;
        }

        return array(
                'maxy' => $maxY,
                'miny' => $minY,
                'maxx' => $maxX,
                'minx' => $minX,
        );
    }

    public function asArray() {
        $array = [];
        foreach ($this->components as $component) {
            $array[] = $component->asArray();
        }
        return $array;
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
        return geoPHP::geometryReduce($components_boundaries);
    }

    public function numGeometries() {
        return count($this->components);
    }

    /**
     * Note that the standard is 1 based indexing
     * @param int $n
     * @return null|Geometry
     */
    public function geometryN($n) {
        return isset($this->components[$n - 1]) ? $this->components[$n - 1] : null;
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
    public function greatCircleLength($radius =  geoPHP::EARTH_EQUATORIAL_RADIUS) {
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

    public function zRange() {
        return abs($this->maximumZ() - $this->minimumZ());
    }

    public function zDifference() {
        $startPoint = $this->startPoint();
        $endPoint = $this->startPoint();
        if ($startPoint && $endPoint && $startPoint->hasZ() && $endPoint->hasZ()) {
            return abs($startPoint->z() - $endPoint->z());
        } else {
            return null;
        }
    }

    public function elevationGain($vertical_tolerance = 3.5) {
        $gain = null;
        foreach ($this->components as $component) {
            $gain += $component->elevationGain($vertical_tolerance);
        }
        return $gain;
    }

    public function elevationLoss($vertical_tolerance = 3.5) {
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

    public function dimension() {
        $dimension = 0;
        foreach ($this->components as $component) {
            if ($component->dimension() > $dimension) {
                $dimension = $component->dimension();
            }
        }
        return $dimension;
    }

    // A collection is empty if it has no components.
    public function isEmpty() {
        return empty($this->components);
    }

    /**
     * @return int
     */
    public function numPoints() {
        $num = 0;
        foreach ($this->components as $component) {
            $num += $component->numPoints();
        }
        return $num;
    }

    /**
     * @return Point[]
     */
    public function getPoints() {
        $points = [];

        // Same as array_merge($points, $component->getPoints()), but 500× faster
        foreach ($this->components as $component1) {
            if ($component1 instanceof Point) {
                $points[] = $component1;
            } else {
                foreach ($component1->components as $component2) {
                    if ($component2 instanceof Point) {
                        $points[] = $component2;
                    } else {
                        foreach ($component2->components as $component3) {
                            if ($component3 instanceof Point) {
                                $points[] = $component3;
                            } else {
                                foreach ($component3->getPoints() as $componentPoints) {
                                    $points[] = $componentPoints;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $points;
    }

    /**
     * @param Geometry $geometry
     * @return bool
     */
    public function equals($geometry) {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->equals($geometry->getGeos());
        }

        // To test for equality we check to make sure that there is a matching point
        // in the other geometry for every point in this geometry.
        // This is slightly more strict than the standard, which
        // uses Within(A,B) = true and Within(B,A) = true
        // @@TODO: Eventually we could fix this by using some sort of simplification
        // method that strips redundant vertices (that are all in a row)

        $this_points = $this->getPoints();
        $other_points = $geometry->getPoints();

        // First do a check to make sure they have the same number of vertices
        if (count($this_points) != count($other_points)) {
            return false;
        }

        foreach ($this_points as $point) {
            $found_match = false;
            foreach ($other_points as $key => $test_point) {
                if ($point->equals($test_point)) {
                    $found_match = true;
                    unset($other_points[$key]);
                    break;
                }
            }
            if (!$found_match) {
                return false;
            }
        }

        // All points match, return TRUE
        return true;
    }

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

    /**
     * Get all line segments
     * @param bool $toArray return segments as LineString or array of start and end points. Explode(true) is faster
     *
     * @return LineString[] | Point[][]
     */
    public function explode($toArray=false) {
        $parts = [];
        foreach ($this->components as $component) {
            foreach ($component->explode($toArray) as $part) {
                $parts[] = $part;
            }
        }
        return $parts;
    }

    public function flatten() {
        if ($this->hasZ()) {
            $new_components = array();
            foreach ($this->components as $component) {
                $new_components[] = $component->flatten();
            }
            $type = $this->geometryType();
            return new $type($new_components);
        }
        else return $this;
    }

    public function distance($geometry) {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
        }
        $distance = NULL;
        foreach ($this->components as $component) {
            $check_distance = $component->distance($geometry);
            if ($check_distance === 0) return 0;
            if ($check_distance === NULL) return NULL;
            if ($distance === NULL) $distance = $check_distance;
            if ($check_distance < $distance) $distance = $check_distance;
        }
        return $distance;
    }

    // Not valid for this geometry type
    // --------------------------------
    public function x() {
        return null;
    }

    public function y() {
        return null;
    }

    public function z() {
        return null;
    }

    public function m() {
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

    /**
     * @param $n
     * @return null
     */
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
