<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
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

    /**
     * Constructor: Checks and sets component geometries
     *
     * @param Geometry[] $components array of geometries
     * @param bool $allowEmpty allow creating geometries with empty components
     * @throws \Exception
     */
    public function __construct($components = [], $allowEmpty = false) {
        if (!is_array($components)) {
            throw new InvalidGeometryException("Component geometries must be passed as an array");
        }
        $componentCount = count($components);
        for ($i=0; $i < $componentCount; ++$i) { // foreach is too memory-intensive here
            if ($components[$i] instanceof Geometry) {
                if (!$allowEmpty && $components[$i]->isEmpty()) {
                    throw new InvalidGeometryException('Cannot create a collection of empty ' . $components[$i]->geometryType() . 's (' . ($i+1) . '. component)');
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

    /**
     * Returns every sub-geometry as a multidimensional array
     *
     * @return array
     */
    public function asArray() {
        $array = [];
        foreach ($this->components as $component) {
            $array[] = $component->asArray();
        }
        return $array;
    }

    public function numGeometries() {
        return count($this->components);
    }

    /**
     * Returns the 1-based Nth geometry.
     *
     * @param int $n 1-based geometry number
     * @return Geometry|null
     */
    public function geometryN($n) {
        return isset($this->components[$n - 1]) ? $this->components[$n - 1] : null;
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
}
