<?php
/**
* GeoJSON class : a geojson reader/writer.
*
* This deviates from the geoJSON spec in that if there is meta data at the root level
* a "properties" property is added to the root of the JSON. This corresponds to the top
* level <metadata> tag in GPX files and is included so we can convert from one to the other
* without losing data.
*
* For routes and tracks it saves them as "features" and adds a line_type property which may be
* 'rte' or 'trk' corresponding to GPX routes and tracks.
*
* Routes waypoints are stored in the coordatinates array of the geometry but the calculated
* points between waypoints are stored in object at position 3 in the coordate array
* that includes an extensions property among others.
*
* The extended GeoJSON for a route has the following format:
*
* {
*	"geometry" : {
*		"type" : "LineString",
*		"coordinates" : [[
*			-77.463442,
*			39.526728,
*			null, {
*				"extensions" : {
*					"gpxx_routepointextension" : [[
*							"-77.463442",
*							"39.526642",
*						],
*						....
*					],
*				},
*				"name" : "Watershed"
*			}
*			],
*			[
*			-77.500843,
*			39.548915,
*			null, {
*				"extensions" : {
*					"gpxx_routepointextension" : [[
*							"-77.500864",
*							"39.548872"
*						],
*						....
* ....
*					]
*				}
*				"name": "Dirt"
*			}]
*		]
*	},
*	"type" : "Feature",
*	"properties" : {
*		"line_type" : "rte",
*		"name" : "Michaux from Watershed"
*	}
* }
*
* GPX waypoints have the following format:
*
*      {
*         "properties" : {
*            "sym" : "Waypoint",
*            "elevation" : "38.10",
*            "extensions" : {
*               "gpxx_waypointextension" : {
*                  "gpxx_address" : {
*                     "gpxx_city" : "Shijr, Taipei County",
*                     "gpxx_country" : "Taiwan",
*                     "gpxx_streetaddress" : "No 68, Jangshu 2nd Road"
*                  }
*               }
*            },
*            "name" : "Garmin Asia"
*         },
*         "geometry" : {
*            "coordinates" : [
*               121.640268,
*               25.061784,
*               38.1
*            ],
*            "type" : "Point"
*         },
*         "type" : "Feature"
*      }
*
*/

class GeoJSON extends GeoAdapter {

	/**
	* Given an object or a string, return a Geometry
	*
	* @param mixed $input The GeoJSON string or object
	*
	* @return object Geometry
	*/

	public function read($input) {

		if (is_string($input)) {

			// intentionally treating the input as an array.

			$input = json_decode( $input, true );
		}

		if (!is_array($input)) {
			throw new Exception('Invalid JSON');
		}

		if (!is_string( $input['type'] )) {
			throw new Exception('Invalid JSON - no type property.');
		}

		if ($input[ 'type' ] == 'FeatureCollection') {
			$geoms = array();

			foreach ( $input['features'] as $feature) {
				$geoms[] = $this->read($feature);
			}

			$geometry = geoPHP::geometryReduce($geoms);

			// this violates the geoJSON spec but allows us to 
			// keep top level GPX <metadata> around.

			if ( isset( $input[ 'properties' ] ) ) {
				$geometry->setMetaData( $input[ 'properties' ] );
			}

			return $geometry;
		}

		if ($input[ 'type' ] == 'Feature') {

			// features must have properties according to the 
			// spec but the test input files from the geoPHP
			// repository don't include them.

			$geom = $this->read( $input['geometry'] ); 

			if ( isset( $input['properties'] ) ) {
				$geom->setMetaData( $input['properties'] );
			}

		}

		// It's a geometry - process it

		return $this->arrayToGeom($input);

	} // end of read()

	private function arrayToGeom($array) {
		$type = $array['type'];

		if ($type == 'GeometryCollection') {
			return $this->arrayToGeometryCollection($array);
		} else if ( $type == 'Feature' ) {
			return $this->arrayToFeature($array);
		}

		$method = 'arrayTo' . $type;
		return $this->$method($array[ 'coordinates' ]);
	}

	private function arrayToFeature($arr) {

		$type = $arr[ 'geometry' ][ 'type' ];

		$geom_obj = $arr[ 'geometry' ];

		$method = 'arrayTo' . $type;
		$feature = $this->$method( $geom_obj[ 'coordinates' ]);

		if ( isset( $arr['properties'] ) ) {
			$feature->setMetaData( $arr[ 'properties' ] );
		}
 
		return $feature;
	}

	/**
	* array to point
	* 
	* array members are:
	*	x, y, [z], [metadata]
	*/

	private function arrayToPoint($array) {
		if (!empty($array)) {

			// for the sake of passing tests

			if ( count( $array ) == 4 ) {
				return new Point( $array[0], $array[1], $array[2], $array[3] );
			} else if ( count( $array ) >= 3 ) {
				return new Point($array[0], $array[1], $array[2] );
			} else {
				return new Point($array[0], $array[1] );
			}

		} else {
			return new Point();
		}
	}

	private function arrayToLineString($array) {

		$points = array();

		foreach ($array as $comp_array) {
			$points[] = $this->arrayToPoint($comp_array);
		}

		return new LineString($points);
	}

	private function arrayToPolygon($array) {

		$lines = array();

		foreach ($array as $comp_array) {
			$lines[] = $this->arrayToLineString($comp_array);
		}

		return new Polygon($lines);
	}

	private function arrayToMultiPoint($array) {
		$points = array();

		foreach ($array as $comp_array) {
			$points[] = $this->arrayToPoint($comp_array);
		}

		return new MultiPoint($points);
	}

	private function arrayToMultiLineString($array) {
		$lines = array();

		foreach ($array as $comp_array) {
			$lines[] = $this->arrayToLineString($comp_array);
		}

		return new MultiLineString($lines);
	}

	private function arrayToMultiPolygon($array) {
		$polys = array();

		foreach ($array as $comp_array) {
			$polys[] = $this->arrayToPolygon($comp_array);
		}

		return new MultiPolygon($polys);
	}

	private function arrayToGeometryCollection($array) {
		$geoms = array();

		if (empty($array[ 'geometries' ])) {
			throw new Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
		}

		foreach ($array[ 'geometries' ] as $comp_array) {
			$geoms[] = $this->arrayToGeom($comp_array);
		}

		return new GeometryCollection($geoms);
	}

	/**
	* Serializes an object into a geojson string
	*
	*
	* @param Geometry $obj The object to serialize
	*
	* @return string The GeoJSON string
	*/

	public function write(Geometry $geometry, $return_array = FALSE) {

		if ($return_array) {
			return $this->getArray($geometry);
		} else {
			return json_encode($this->getArray($geometry));
		}
	}

	/**
	* given a geometry generate an array.
	*
	* @todo does not check for invalid geometry/feature heirarchy.
	*/

	public function getArray($geometry) {
		$isFeatureCollection = false;

		if ($geometry->getGeomType() == 'GeometryCollection') {
			$component_array = array();

			foreach ($geometry->components as $component) {

				// if we have meta data we need to wrap this geometry
				// in a feature

				if (( $meta_data = $component->getMetaData() ) != NULL ) {

					$isFeatureCollection = true;

					$component_array[] = array(
						'type' => 'Feature',
						'properties' => $meta_data,
						'geometry' => array (
							'type' => $component->geometryType(),
							'coordinates' => $component->asArray()
						)
					);

				} else {

					$component_array[] = array(
						'type' => $component->geometryType(),
						'coordinates' => $component->asArray(),
					);
				}
			}

			if ( $isFeatureCollection ) {

				return array(
					'type'=> 'FeatureCollection',

					// this violates the geoJSON spec but is needed so we can 
					// convert to and from GPX files with a top level <metadata> tag.

					'properties' => $geometry->getMetaData(),
					'features'=> $component_array,
				);

			} else {

				return array(
					'type'=> 'GeometryCollection',
					'geometries'=> $component_array,
				);
			}

		} else {

			if (( $meta_data = $geometry->getMetaData() ) != NULL ) {

				return array(
					'type' => 'Feature',
					'properties' => $meta_data,
					'geometry' => array (
						'type' => $geometry->geometryType(),
						'coordinates' => $geometry->asArray()
					)
				);

			} else {

				return array(
					'type'=> $geometry->getGeomType(),
					'coordinates'=> $geometry->asArray(),
				);
			}
		}

	} // end of getArray()
}


