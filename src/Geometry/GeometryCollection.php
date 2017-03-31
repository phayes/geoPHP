<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * GeometryCollection: A heterogenous collection of geometries  
 */
class GeometryCollection extends MultiGeometry {

	/**
	 * @param Geometry[] $components Array of geometries. Components of GeometryCollection can be
	 *     any of valid Geometry types, including empty geometry
	 *
	 * @throws \Exception
	 */
	public function __construct($components = []) {
		parent::__construct($components, true);
	}

	public function geometryType() {
		return Geometry::GEOMETRY_COLLECTION;
	}

	public function dimension() {
		$dimension = 0;
		foreach ($this->getComponents() as $component) {
			if ($component->dimension() > $dimension) {
				$dimension = $component->dimension();
			}
		}
		return $dimension;
	}

	/**
	 * Not valid for this geometry type
	 * @return null
	 */
	public function isSimple() {
		return null;
	}

	/**
	 * In a GeometryCollection, the centroid is equal to the centroid of the set of component Geometries of highest dimension
	 * (since the lower-dimension geometries contribute zero "weight" to the centroid).
	 *
	 * @return Point
	 *
	 * @throws \Exception
	 */
	public function centroid() {
		if ($this->isEmpty()) {
			return new Point();
		}

		if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
			return geoPHP::geosToGeometry($this->getGeos()->centroid());
		}

		$geometries = $this->explodeGeometries();

		$highestDimension = 0;
		foreach ($geometries as $geometry) {
			if ($geometry->dimension() > $highestDimension) {
				$highestDimension = $geometry->dimension();
			}
			if ($highestDimension === 2) {
				break;
			}
		}

		$highestDimensionGeometries = [];
		foreach ($geometries as $geometry) {
			if ($geometry->dimension() === $highestDimension) {
				$highestDimensionGeometries[] = $geometry;
			}
		}

		$reducedGeometry = geoPHP::geometryReduce($highestDimensionGeometries);
		if($reducedGeometry->geometryType() === Geometry::GEOMETRY_COLLECTION) {
			throw new \Exception('Internal error: GeometryCollection->centroid() calculation failed.');
		}
		return $reducedGeometry->centroid();
	}

	/**
	 * Returns every sub-geometry as a multidimensional array
	 *
	 * Because geometryCollections are heterogeneous we need to specify which type of geometries they contain.
	 * We need to do this because, for example, there would be no way to tell the difference between a
	 * MultiPoint or a LineString, since they share the same structure (collection
	 * of points). So we need to call out the type explicitly.
	 *
	 * @return array
	 */
	public function asArray() {
		$array = [];
		foreach ($this->getComponents() as $component) {
			$array[] = [
					'type'       => $component->geometryType(),
					'components' => $component->asArray(),
			];
		}
		return $array;
	}

	/**
	 * @return Geometry[]|Collection[]
	 */
	public function explodeGeometries() {
		$geometries = [];
		foreach ($this->components as $component) {
			if ($component->geometryType() === Geometry::GEOMETRY_COLLECTION) {
				/** @var GeometryCollection $component */
				$geometries = array_merge($geometries, $component->explodeGeometries());
			} else {
				$geometries[] = $component;
			}
		}
		return $geometries;
	}

	// Not valid for this geometry
	public function boundary() { return NULL; }
}

