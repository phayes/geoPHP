<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * Polygon: A polygon is a plane figure that is bounded by a closed path,
 * composed of a finite sequence of straight line segments
 *
 * @method LineString[] getComponents()
 */
class Polygon extends Collection {

    /**
     * @param LineString[] $components
     * @param bool|false $forceCreate
     * @throws \Exception
     */
    public function __construct($components = array(), $forceCreate = false) {
        parent::__construct($components);

        foreach ($this->getComponents() as $i => $component) {
            if ($component->numPoints() < 4) {
                throw new \Exception('Cannot create Polygon: Invalid number of points in LinearRing. Found ' . $component->numPoints() . ', expected more than 3 or 0');
            }
            if (!$component->isClosed()) {
                if ($forceCreate) {
                    $this->components[$i] = new LineString(array_merge($component->getComponents(), [$component->startPoint()]));
                } else {
                    throw new \Exception('Cannot create Polygon: contains non-closed ring (first point: '
                            . implode(' ', $component->startPoint()->asArray()) . ', last point: '
                            . implode(' ', $component->endPoint()->asArray()) .')');
                }
            }
            // This check is tooo expensive
            //if (!$component->isSimple() && !$forceCreate) {
            //    throw new \Exception('Cannot create Polygon: geometry should be simple');
            //}
        }

    }

    public function geometryType() {
        return Geometry::POLYGON;
    }

    public function dimension() {
        return 2;
    }

    /**
     * @param bool|false $exterior_only Calculate the area of exterior ring only, or the polygon with holes
     * @param bool|false $signed Usually we want to get positive area, but vertices order (CW or CCW) can be determined from signed area.
     * @return float|null|number
     */
    public function area($exterior_only = false, $signed = false) {
        if ($this->isEmpty()) {
            return 0;
        }

        if ($this->getGeos() && $exterior_only == false) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->area();
        }

        /** @var LineString $exterior_ring */
        $exterior_ring = $this->components[0];
        $points = $exterior_ring->getComponents();

        $pointCount = count($points);
        if ($pointCount === 0) {
            return null;
        }
        $a = 0;
        foreach ($points as $k => $p) {
            $j = ($k + 1) % $pointCount;
            $a = $a + ($p->x() * $points[$j]->y()) - ($p->y() * $points[$j]->x());
        }

        $area = $signed ? ($a / 2) : abs(($a / 2));

        if ($exterior_only == true) {
            return $area;
        }
        foreach ($this->components as $delta => $component) {
            if ($delta != 0) {
                $inner_poly = new Polygon(array($component));
                $area -= $inner_poly->area();
            }
        }
        return (float) $area;
    }

    public function centroid() {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->centroid());
        }

        $x=0;
        $y=0;
        $totalArea = 0;
        foreach ($this->getComponents() as $i => $component) {
            $ca = $this->getRingCentroidAndArea($component);
            if ($i == 0) {
                $totalArea += $ca['area'];
                $x += $ca['x'] * $ca['area'];
                $y += $ca['y'] * $ca['area'];
            } else {
                $totalArea -= $ca['area'];
                $x += $ca['x'] * $ca['area'] * -1;
                $y += $ca['y'] * $ca['area'] * -1;
            }
        }
        if ($totalArea == 0.0) {
            return new Point();
        }
        return new Point($x / $totalArea, $y / $totalArea);
    }

    /**
     * @param LineString $ring
     * @return array
     */
    protected function getRingCentroidAndArea($ring) {
        $area = (new Polygon([$ring]))->area(true, true);

        $points = $ring->getPoints();
        $pointCount = count($points);
        if ($pointCount === 0 || $area == 0.0) {
            return ['area' => 0, 'x' => null, 'y' => null];
        }
        $x=0;
        $y=0;
        foreach ($points as $k => $point) {
            $j = ($k + 1) % $pointCount;
            $P = ($point->x() * $points[$j]->y()) - ($point->y() * $points[$j]->x());
            $x += ($point->x() + $points[$j]->x()) * $P;
            $y += ($point->y() + $points[$j]->y()) * $P;
        }
        return ['area' => abs($area), 'x' => $x / (6 * $area), 'y' => $y / (6 * $area)];
    }

    /**
     * Find the outermost point from the centroid
     *
     * @returns Point The outermost point
     */
    public function outermostPoint() {
        $centroid = $this->centroid();
        if ($centroid->isEmpty()) {
            return $centroid;
        }

        $maxDistance = 0;
        $maxPoint = null;

        foreach ($this->exteriorRing()->getPoints() as $point) {
            $distance = $centroid->distance($point);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxPoint = $point;
            }
        }

        return $maxPoint;
    }

    /**
     * @return LineString
     */
    public function exteriorRing() {
        if ($this->isEmpty()) {
            return new LineString();
        }
        return $this->components[0];
    }

    public function numInteriorRings() {
        if ($this->isEmpty()) {
            return 0;
        }
        return $this->numGeometries() - 1;
    }

    public function interiorRingN($n) {
        return $this->geometryN($n + 1);
    }

    public function isSimple() {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
        }

        $segments = $this->explode(true);

        //TODO: instead of this O(n^2) algorithm implement Shamos-Hoey Algorithm which is only O(n*log(n))
        foreach ($segments as $i => $segment) {
            foreach ($segments as $j => $check_segment) {
                if ($i != $j) {
                    if (Geometry::segmentIntersects($segment[0], $segment[1], $check_segment[0], $check_segment[1])) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * For a given point, determine whether it's bounded by the given polygon.
     * Adapted from @source http://www.assemblysys.com/dataServices/php_pointinpolygon.php
     *
     * @see http://en.wikipedia.org/wiki/Point%5Fin%5Fpolygon
     *
     * @param Point $point
     * @param boolean $pointOnBoundary - whether a boundary should be considered "in" or not
     * @param boolean $pointOnVertex - whether a vertex should be considered "in" or not
     * @return boolean
     */
    public function pointInPolygon($point, $pointOnBoundary = true, $pointOnVertex = true) {
        $vertices = $this->getPoints();

        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex($point, $vertices)) {
            return $pointOnVertex ? true : false;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i = 1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if ($vertex1->y() == $vertex2->y()
                    && $vertex1->y() == $point->y()
                    && $point->x() > min($vertex1->x(), $vertex2->x())
                    && $point->x() < max($vertex1->x(), $vertex2->x())
            ) {
                // Check if point is on an horizontal polygon boundary
                return $pointOnBoundary ? true : false;
            }
            if ($point->y() > min($vertex1->y(), $vertex2->y())
                    && $point->y() <= max($vertex1->y(), $vertex2->y())
                    && $point->x() <= max($vertex1->x(), $vertex2->x())
                    && $vertex1->y() != $vertex2->y()
            ) {
                $xinters =
                        ($point->y() - $vertex1->y()) * ($vertex2->x() - $vertex1->x())
                        / ($vertex2->y() - $vertex1->y())
                        + $vertex1->x();
                if ($xinters == $point->x()) {
                    // Check if point is on the polygon boundary (other than horizontal)
                    return $pointOnBoundary ? true : false;
                }
                if ($vertex1->x() == $vertex2->x() || $point->x() <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is even, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Point $point
     * @return bool
     */
    public function pointOnVertex($point) {
        foreach ($this->getPoints() as $vertex) {
            if ($point->equals($vertex)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether the given geometry is spatially inside the Polygon
     * TODO: rewrite this. Currently supports point, linestring and polygon with only outer ring
     * @param Geometry $geometry
     * @return bool
     */
    public function contains($geometry) {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->contains($geometry->getGeos());
        }

        $isInside = false;
        foreach ($geometry->getPoints() as $p) {
          if ($this->pointInPolygon($p)) {
              $isInside = true; // at least one point of the innerPoly is inside the outerPoly
              break;
          }
        }
        if (!$isInside) {
            return false;
        }

        if ($geometry->geometryType() == Geometry::LINE_STRING) {
        } else if ($geometry->geometryType() == Geometry::POLYGON) {
            $geometry = $geometry->exteriorRing();
        } else {

            return false;
        }

        foreach ($geometry->explode(true) as $innerEdge) {
            foreach ($this->exteriorRing()->explode(true) as $outerEdge) {
                if (Geometry::segmentIntersects($innerEdge[0], $innerEdge[1], $outerEdge[0], $outerEdge[1])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getBBox() {
        return $this->exteriorRing()->getBBox();
    }

    public function length() {
        return null;
    }

}

