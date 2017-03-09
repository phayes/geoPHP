<?php

namespace geoPHP\Geometry;
use geoPHP\geoPHP;

/**
 * LineString. A collection of Points representing a line.
 * A line can have more than one segment.
 *
 * @method Point[] getComponents()
 */
class LineString extends Collection {

    private $startPoint = null;
    private $endPoint = null;

    /**
     * Constructor
     *
     * @param Point[] $points An array of at least two points with
     * which to build the LineString
     * @throws \Exception
     */
    public function __construct($points = array()) {
        if (count($points) == 1) {
            throw new \Exception("Cannot construct a LineString with a single point");
        }

        // Call the Collection constructor to build the LineString
        parent::__construct($points);
    }

    public function geometryType() {
        return 'LineString';
    }

    public function dimension() {
        return 1;
    }

    // The boundary of a linestring is itself
    public function boundary() {
        return $this;
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

    public function numPoints() {
        return count($this->components);
    }

    /**
     * @param int $n
     * @return null|Point
     */
    public function pointN($n) {
        return $this->geometryN($n);
    }

    public function area() {
        return 0;
    }

    public function centroid() {
        return $this->getCentroidAndLength();
    }

    public function getCentroidAndLength(&$length=0) {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->centroid());
        }

        $x = 0;
        $y = 0;
        $length = 0;
        /** @var Point $previousPoint */
        $previousPoint = null;
        foreach($this->getPoints() as $point) {
            if ($previousPoint) {
                // Equivalent to $previousPoint->distance($point) but much faster
                $segmentLength = sqrt(
                        pow(($previousPoint->x() - $point->x()), 2) +
                        pow(($previousPoint->y() - $point->y()), 2)
                );
                $length += $segmentLength;
                $x += ($previousPoint->x() + $point->x()) / 2 * $segmentLength;
                $y += ($previousPoint->y() + $point->y()) / 2 * $segmentLength;
            }
            $previousPoint = $point;
        }
        if ($length == 0) {
            return $this->startPoint();
        }
        return new Point($x / $length , $y / $length);
    }

    /**
     *  Returns the length of this Curve in its associated spatial reference.
     * Eg. if Geometry is in geographical coordinate system it returns the length in degrees
     * @return float|int
     */
    public function length() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->length();
        }
        $length = 0;
        /** @var Point $previousPoint */
        $previousPoint = null;
        foreach ($this->getPoints() as $delta => $point) {
            if ($previousPoint) {
				$length += sqrt(
						pow(($previousPoint->x() - $point->x()), 2) +
						pow(($previousPoint->y() - $point->y()), 2)
				);
            }
            $previousPoint = $point;
        }
        return $length;
    }

    public function length3D() {
        $length = 0;
		/** @var Point $previousPoint */
		$previousPoint = null;
		foreach ($this->getPoints() as $delta => $point) {
			if ($previousPoint) {
				$length += sqrt(
						pow(($previousPoint->x() - $point->x()), 2) +
						pow(($previousPoint->y() - $point->y()), 2) +
						pow(($previousPoint->z() - $point->z()), 2)
				);
			}
			$previousPoint = $point;
		}
        return $length;
    }

	/**
	 * @param int $radius Earth radius
	 * @return float Great Circle length in meters
	 */
	public function greatCircleLength($radius = geoPHP::EARTH_EQUATORIAL_RADIUS) {
		$length = 0;
		$points = $this->getPoints();
		for ($i = 0; $i < $this->numPoints() - 1; $i++) {
			$point = $points[$i];
			$nextPoint = $points[$i + 1];
			if (!is_object($nextPoint)) {
				continue;
			}
			// Great circle method
			$lat1 = deg2rad($point->y());
			$lat2 = deg2rad($nextPoint->y());
			$lon1 = deg2rad($point->x());
			$lon2 = deg2rad($nextPoint->x());
			$dlon = $lon2 - $lon1;
			$d =
					$radius *
					atan2(
							sqrt(
									pow(cos($lat2) * sin($dlon), 2) +
									pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dlon), 2)
							)
							,
							sin($lat1) * sin($lat2) +
							cos($lat1) * cos($lat2) * cos($dlon)
					);
			if ($point->hasZ() && $nextPoint->hasZ()) {
				$d = sqrt(
						pow($d, 2) +
						pow($nextPoint->z() - $point->z(), 2)
				);
			}

			$length += $d;
		}
		// Returns length in meters.
		return $length;
	}

	/**
	 * @return float Haversine length of geometry in degrees
	 */
    public function haversineLength() {
        $degrees = 0;
        $points = $this->getPoints();
        for ($i = 0; $i < $this->numPoints() - 1; $i++) {
            $point = $points[$i];
            $next_point = $points[$i + 1];
            if (!is_object($next_point)) {
                continue;
            }
            $degree = geoPHP::EARTH_EQUATORIAL_RADIUS * (
                    acos(
                            sin(deg2rad($point->y())) * sin(deg2rad($next_point->y())) +
                            cos(deg2rad($point->y())) * cos(deg2rad($next_point->y())) *
                            cos(deg2rad(abs($point->x() - $next_point->x())))
                    )
            );
            $degrees += $degree;
        }
        // Returns degrees
        return $degrees;
    }

    /**
     * @return float Haversine length of geometry in degrees
     */
    public function haversineLength2() {
        $distance = 0;
        $points = $this->getPoints();
        for ($i = 0; $i < $this->numPoints() - 1; $i++) {
            $point = $points[$i];
            $next_point = $points[$i + 1];
            if (!is_object($next_point)) {
                continue;
            }
            $distance = geoPHP::EARTH_EQUATORIAL_RADIUS *
                    asin(sqrt(
                            pow(sin((deg2rad($next_point->y()) - deg2rad($point->y())) / 2), 2) +
                            cos(deg2rad($point->y())) * cos(deg2rad($next_point->y())) *
                            pow(sin((deg2rad($next_point->x()) - deg2rad($point->x())) / 2), 2)
                    ));
            $distance += $distance;
        }
        // Returns degrees
        return $distance;
    }

	public function minimumZ() {
		$min = PHP_INT_MAX;
		foreach ($this->getPoints() as $point) {
			if ($point->hasZ() && $point->z() < $min) {
				$min = $point->z();
			}
		}
		return $min < PHP_INT_MAX ? $min : null;
	}

	public function maximumZ() {
		$max = ~PHP_INT_MAX;
		foreach ($this->getPoints() as $point) {
			if ($point->hasZ() && $point->z() > $max) {
				$max = $point->z();
			}
		}

		return $max > ~PHP_INT_MAX ? $max : null;
	}

	public function zRange() {
		return abs($this->maximumZ() - $this->minimumZ());
	}

	public function zDifference() {
		if ($this->startPoint()->hasZ() && $this->endPoint()->hasZ()) {
			return abs($this->startPoint()->z() - $this->endPoint()->z());
		} else {
			return null;
		}
	}

	public function elevationGain($vertical_tolerance) {
		$gain = 0;
		$last_ele = $this->startPoint()->z();
		foreach ($this->getPoints() as $point) {
			if (abs($point->z() - $last_ele) > $vertical_tolerance) {
				if ($point->z() > $last_ele) {
					$gain += $point->z() - $last_ele;
				}
				$last_ele = $point->z();
			}
		}
		return $gain;
	}

	public function elevationLoss($vertical_tolerance) {
		$loss = 0;
		$last_ele = $this->startPoint()->z();
		foreach ($this->getPoints() as $point) {
			if (abs($point->z() - $last_ele) > $vertical_tolerance) {
				if ($point->z() < $last_ele) {
					$loss += $last_ele - $point->z();
				}
				$last_ele = $point->z();
			}
		}
		return $loss;
	}

    public function minimumM() {
        $min = PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->isMeasured() && $point->m() < $min) {
                $min = $point->m();
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumM() {
        $max = ~PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->isMeasured() && $point->m() > $max) {
                $max = $point->m();
            }
        }

        return $max > ~PHP_INT_MAX ? $max : null;
    }


    /**
     * Get all line segments
     * @param bool $toArray return segments as LineString or array of start and end points
     *
     * @return LineString[]|array[Point]
     */
    public function explode($toArray=false) {
        $parts = array();
        $points = $this->getPoints();
        if (!$toArray) {
            foreach ($points as $i => $point) {
                if (isset($points[$i + 1])) {
                    $parts[] = new LineString(array($point, $points[$i + 1]));
                }
            }
        } else {
            if (count($points) < 2) {
                return [];
            }
            $lastPoint = $points[0];
            for ($i=1; $i < count($points); $i++) {
                $parts[] = [$lastPoint, $points[$i]];
                $lastPoint = $points[$i];
            }}
        return $parts;
    }

    /**
     * Checks that LineString is a Simple Geometry
     * @return boolean
     *
     * @see http://lists.osgeo.org/pipermail/postgis-devel/attachments/20041222/f8c95036/attachment.obj
     */
    public function isSimple() {
        if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
        }

        if ($this->hasZ()
                && $this->startPoint()->equals($this->endPoint())
                && $this->startPoint()->z() !== $this->endPoint()->z()) {
            return false;
        }

		$segments = $this->explode(true);
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
	 * @param $segment LineString
	 * @return bool
	 */
    public function lineSegmentIntersect($segment) {
        return Geometry::segmentIntersects(
                $this->startPoint(), $this->endPoint(),
                $segment->startPoint(), $segment->endPoint()
        );
    }

    /**
     * @param Geometry|Collection $geometry
     * @return float|null
     */
    public function distance($geometry) {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
        }

        if ($geometry->geometryType() == 'Point') {
            // This is defined in the Point class nicely
            return $geometry->distance($this);
        }
        if ($geometry->geometryType() == 'LineString') {
            $distance = NULL;
			$geometrySegments = $geometry->explode();
            foreach ($this->explode() as $seg1) {
                /** @var LineString $seg2 */
                foreach ($geometrySegments as $seg2) {
                    if ($seg1->lineSegmentIntersect($seg2)) return 0;
                    // Because line-segments are straight, the shortest distance will occur at an endpoint.
                    // If they are parallel an endpoint calculation is still accurate.
                    $check_distance_1 = $seg1->startPoint()->distance($seg2);
                    $check_distance_2 = $seg1->endPoint()->distance($seg2);
                    $check_distance_3 = $seg2->startPoint()->distance($seg1);
                    $check_distance_4 = $seg2->endPoint()->distance($seg1);

                    $check_distance = min($check_distance_1, $check_distance_2, $check_distance_3, $check_distance_4);
                    if ($distance === NULL) $distance = $check_distance;
                    if ($check_distance < $distance) $distance = $check_distance;
                    if ($distance === 0.0) return 0;
                }
            }
            return $distance;
        }
        else {
            // It can be treated as collection
            return parent::distance($geometry);
        }
    }
}

