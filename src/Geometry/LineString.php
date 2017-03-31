<?php

namespace geoPHP\Geometry;
use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

/**
 * A LineString is defined by a sequence of points, (X,Y) pairs, which define the reference points of the line string.
 * Linear interpolation between the reference points defines the resulting linestring.
 *
 * @method Point[] getComponents()
 */
class LineString extends Curve {

    public function geometryType() {
        return Geometry::LINE_STRING;
    }

    /**
     * Constructor
     *
     * @param Point[] $points An array of at least two points with
     * which to build the LineString
     * @throws \Exception
     */
    public function __construct($points = []) {
        // Call the Collection constructor to build the LineString
        parent::__construct($points);

        if (count($points) == 1) {
            throw new InvalidGeometryException("Cannot construct a LineString with a single point");
        }
    }

    public static function fromArray($array) {
        $points = [];
        foreach ($array as $point) {
            $points[] = Point::fromArray($point);
        }
        return new static($points);
    }

    /**
     * Returns the number of points of the LineString
     *
     * @return int
     */
    public function numPoints() {
        return count($this->components);
    }

    /**
	 * Returns the 1-based Nth point of the LineString.
	 * Negative values are counted backwards from the end of the LineString.
	 *
     * @param int $n Nth point of the LineString
     * @return Point|null
     */
    public function pointN($n) {
        return $n >= 0
				? $this->geometryN($n)
				: $this->geometryN(count($this->components) - abs($n + 1));
    }

    public function centroid() {
        return $this->getCentroidAndLength();
    }

    public function getCentroidAndLength(&$length=0) {
        if ($this->isEmpty()) {
            return new Point();
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
        foreach ($this->getPoints() as $point) {
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
		foreach ($this->getPoints() as $point) {
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
	 * @param float|null $radius Earth radius
	 * @return float Length in meters
	 */
	public function greatCircleLength($radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS) {
		$length = 0;
        $rad = M_PI / 180;
		$points = $this->getPoints();
        $numPoints = $this->numPoints() - 1;
		for ($i = 0; $i < $numPoints; ++$i) {
			// Simplified Vincenty formula with equal major and minor axes (a sphere)
			$lat1 = $points[$i]->y() * $rad;
			$lat2 = $points[$i+1]->y() * $rad;
			$lon1 = $points[$i]->x() * $rad;
			$lon2 = $points[$i+1]->x() * $rad;
			$deltaLon = $lon2 - $lon1;
			$d =
					$radius *
					atan2(
							sqrt(
									pow(cos($lat2) * sin($deltaLon), 2) +
									pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLon), 2)
							)
							,
							sin($lat1) * sin($lat2) +
							cos($lat1) * cos($lat2) * cos($deltaLon)
					);
			if ($points[$i]->is3D()) {
				$d = sqrt(
						pow($d, 2) +
						pow($points[$i+1]->z() - $points[$i]->z(), 2)
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
        $distance = 0;
        $points = $this->getPoints();
        $numPoints = $this->numPoints() - 1;
        for ($i = 0; $i < $numPoints; ++$i) {
            $point = $points[$i];
            $next_point = $points[$i + 1];
            $degree = (geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS *
                    acos(
                            sin(deg2rad($point->y())) * sin(deg2rad($next_point->y())) +
                            cos(deg2rad($point->y())) * cos(deg2rad($next_point->y())) *
                            cos(deg2rad(abs($point->x() - $next_point->x())))
                    )
            );
            $distance += $degree;
        }
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

	public function zDifference() {
		if ($this->startPoint()->hasZ() && $this->endPoint()->hasZ()) {
			return abs($this->startPoint()->z() - $this->endPoint()->z());
		} else {
			return null;
		}
	}

	/**
	 * Returns the cumulative elevation gain of the LineString
	 *
	 * @param int $verticalTolerance Smoothing factor filtering noisy elevation data.
	 *      Its unit equals to the z-coordinates unit (meters for geographical coordinates)
	 *      If the elevation data comes from a DEM, a value around 3.5 can be acceptable.
	 *
	 * @return float
	 */
	public function elevationGain($verticalTolerance = 0) {
		$gain = 0.0;
		$lastEle = $this->startPoint()->z();
		$pointCount = $this->numPoints();
		foreach ($this->getPoints() as $i => $point) {
			if (abs($point->z() - $lastEle) > $verticalTolerance || $i === $pointCount-1) {
				if ($point->z() > $lastEle) {
					$gain += $point->z() - $lastEle;
				}
				$lastEle = $point->z();
			}
		}
		return $gain;
	}

	/**
	 * Returns the cumulative elevation loss of the LineString
	 *
	 * @param int $verticalTolerance Smoothing factor filtering noisy elevation data.
	 *      Its unit equals to the z-coordinates unit (meters for geographical coordinates)
	 *      If the elevation data comes from a DEM, a value around 3.5 can be acceptable.
	 *
	 * @return float
	 */
	public function elevationLoss($verticalTolerance = 0) {
		$loss = 0.0;
		$lastEle = $this->startPoint()->z();
		$pointCount = $this->numPoints();
		foreach ($this->getPoints() as $i => $point) {
			if (abs($point->z() - $lastEle) > $verticalTolerance || $i === $pointCount-1) {
				if ($point->z() < $lastEle) {
					$loss += $lastEle - $point->z();
				}
				$lastEle = $point->z();
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
        $points = $this->getPoints();
        $numPoints = count($points);
        if ($numPoints < 2) {
            return [];
        }
        $parts = [];
        for ($i = 1; $i < $numPoints; ++$i) {
            $segment = [$points[$i - 1], $points[$i]];
            $parts[] = $toArray ? $segment : new LineString($segment);
        }
        return $parts;
    }

    /**
     * Checks that LineString is a Simple Geometry
     *
     * @return boolean
     */
    public function isSimple() {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
        }

        // As of OGR specification a ring is simple only if its start and end points equals in all coordinates
        // Neither GEOS, nor PostGIS support it
//        if ($this->hasZ()
//                && $this->startPoint()->equals($this->endPoint())
//                && $this->startPoint()->z() !== $this->endPoint()->z()
//        ) {
//            return false;
//        }

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

        if ($geometry->geometryType() == Geometry::POINT) {
            // This is defined in the Point class nicely
            return $geometry->distance($this);
        }
        if ($geometry->geometryType() == Geometry::LINE_STRING) {
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

