<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;

/**
 * GeometryCollection: A heterogenous collection of geometries  
 */
class GeometryCollection extends Collection {

	/**
	 * We need to override asArray. Because geometryCollections are heterogeneous
	 * we need to specify which type of geometries they contain. We need to do this
	 * because, for example, there would be no way to tell the difference between a
	 * MultiPoint or a LineString, since they share the same structure (collection
	 * of points). So we need to call out the type explicitly.
	 * @return array
	 */
	public function asArray() {
		$array = array();
		foreach ($this->getComponents() as $component) {
			$array[] = array(
					'type' => $component->geometryType(),
					'components' => $component->asArray(),
			);
		}
		return $array;
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

	public function geometryType() {
		return 'GeometryCollection';
	}

	public function centroid() {
		if ($this->isEmpty()) {
			return null;
		}

		if ($this->getGeos()) {
			/** @noinspection PhpUndefinedMethodInspection */
			return geoPHP::geosToGeometry($this->getGeos()->centroid());
		}

		// Returns a single geometry if possible, or a geometry collection
		$reducedGeometries = geoPHP::geometryReduce($this->getComponents());
		// For a single geometry we can calculate centroid
		if ($reducedGeometries->geometryType() !== 'GeometryCollection') {
			return $reducedGeometries->centroid();
		}

		// Otherwise collect the set of components of highest spatial dimension
		// TODO: rewrite with using dimension()
		$typeDimensions = [
				'MultiPolygon' => 3, 'Polygon' => 3,
				'MultiLineString' => 2, 'LineString' => 2,
				'MultiPoint' => 1, 'Point' => 1,
				'GeometryCollection' => 0 // FIXME: geometryReduce cant handle nested GeomCollections. Now we ignoring them
		];
		$dimension = 0;
		foreach ($reducedGeometries->getComponents() as $geometry) {
			if ($typeDimensions[$geometry->geometryType()] > $dimension) {
				$dimension = $typeDimensions[$geometry->geometryType()];
			}
		}
		$geometries = [];
		foreach ($reducedGeometries->getComponents() as $geometry) {
			if ($typeDimensions[$geometry->geometryType()] === $dimension) {
				$geometries[] = $geometry;
			}
		}

		// Now all geometry has the same dimension, try again
		return (new GeometryCollection($geometries))->centroid();
	}

	// Not valid for this geometry
	public function boundary() { return NULL; }
	public function isSimple() { return NULL; }
}

