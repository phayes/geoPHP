<?php

namespace geoPHP\Adapter;

use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiLineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\MultiPolygon;

/**
 * GeoJSON class : a geoJSON reader/writer.
 *
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJSON implements GeoAdapter {
    /**
     * Given an object or a string, return a Geometry
     *
     * @param string|object $input The GeoJSON string or object
     * @return Geometry
     * @throws \Exception
     */
    public function read($input) {
        if (is_string($input)) {
            $input = json_decode($input);
        }
        if (!is_object($input)) {
            throw new \Exception('Invalid JSON');
        }
        if (!is_string($input->type)) {
            throw new \Exception('Invalid JSON');
        }

        // Check to see if it's a FeatureCollection
        if ($input->type == 'FeatureCollection') {
            $geometries = array();
            foreach ($input->features as $feature) {
                $geometries[] = $this->read($feature);
            }
            return geoPHP::buildGeometry($geometries);
        }

        // Check to see if it's a Feature
        if ($input->type == 'Feature') {
            return $this->geoJSONFeatureToGeometry($input);
        }

        // It's a geometry - process it
        return $this->geoJSONObjectToGeometry($input);
    }

    /**
     * @param object $input
     * @return string|null
     */
    private function getSRID($input) {
        if (isset($input->crs->properties->name)) {
            // parse CRS codes in forms "EPSG:1234" and "urn:ogc:def:crs:EPSG::1234"
            preg_match('#EPSG[:]+(\d+)#', $input->crs->properties->name, $m);
            return isset($m[1]) ? $m[1]: null;
        }
        return null;
    }

    /**
     * @param $obj
     * @return Geometry
     * @throws \Exception
     */
    private function geoJSONFeatureToGeometry($obj) {
        $geometry = $this->read($obj->geometry);
        if (isset($obj->properties)) {
            foreach($obj->properties as $property => $value) {
                $geometry->setData($property, $value);
            }
        }

        return $geometry;
    }

    /**
     * @param object $obj
     * @return Geometry
     * @throws \Exception
     */
    private function geoJSONObjectToGeometry($obj) {
        $type = $obj->type;

        if ($type == 'GeometryCollection') {
            return $this->geoJSONObjectToGeometryCollection($obj);
        }
        $method = 'arrayTo' . $type;
        /** @var GeometryCollection $geometry */
        $geometry = $this->$method($obj->coordinates);
        $geometry->setSRID($this->getSRID($obj));
        return $geometry;
    }

    /**
     * @param [] $coordinates Array of coordinates
     * @return Point
     */
    private function arrayToPoint($coordinates) {
        switch (count($coordinates)) {
            case 2:
                return new Point($coordinates[0], $coordinates[1]);
                break;
            case 3:
                return new Point($coordinates[0], $coordinates[1], $coordinates[2]);
                break;
            case 4:
                return new Point($coordinates[0], $coordinates[1], $coordinates[2], $coordinates[3]);
                break;
            default:
                return new Point();
        }
    }

    private function arrayToLineString($array) {
        $points = array();
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }
        $linestring = new LineString($points);
		return $linestring;
    }

    private function arrayToPolygon($array) {
        $lines = array();
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }
        return new Polygon($lines);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param array $array
     * @return MultiPoint
     */
    private function arrayToMultiPoint($array) {
        $points = array();
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }
        return new MultiPoint($points);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param array $array
     * @return MultiLineString
     */
    private function arrayToMultiLineString($array) {
        $lines = array();
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }
        return new MultiLineString($lines);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param array $array
     * @return MultiPolygon
     */
    private function arrayToMultiPolygon($array) {
        $polygons = array();
        foreach ($array as $comp_array) {
            $polygons[] = $this->arrayToPolygon($comp_array);
        }
        return new MultiPolygon($polygons);
    }

    /**
     * @param $obj
     * @throws \Exception
     * @return GeometryCollection
     */
    private function geoJSONObjectToGeometryCollection($obj) {
        $geometries = array();
        if (!property_exists($obj, 'geometries')) {
            throw new \Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
        }
        foreach ($obj->geometries ?: [] as $comp_object) {
            $geometries[] = $this->geoJSONObjectToGeometry($comp_object);
        }
        $collection = new GeometryCollection($geometries);
        $collection->setSRID($this->getSRID($obj));
        return $collection;
    }

    /**
     * Serializes an object into a geojson string
     *
     *
     * @param Geometry $geometry The object to serialize
     * @param boolean $return_array
     * @return string The GeoJSON string
     */
    public function write(Geometry $geometry, $return_array = false) {
        if ($return_array) {
            return $this->getArray($geometry);
        } else {
            return json_encode($this->getArray($geometry));
        }
    }



    /**
     * Creates a geoJSON array
     *
     * If the root geometry is a GeometryCollection, and any of its geometries has data,
     * the root element will be a FeatureCollection with Feature elements (with the data)
     * If the root geometry has data, it will be included in a Feature object that contains the data
     *
     * The geometry should have geographical coordinates since CRS support has been removed from from geoJSON specification (RFC 7946)
     * The geometry should'nt be measured, since geoJSON specification (RFC 7946) only supports the dimensional positions
     *
     * @param Geometry|GeometryCollection $geometry
     * @param bool|null $isRoot Is geometry the root geometry?
     * @return array
     */
    public function getArray($geometry, $isRoot = true) {
        if ($geometry->geometryType() === Geometry::GEOMETRY_COLLECTION) {
            $components = [];
            $isFeatureCollection = false;
            foreach ($geometry->getComponents() as $component) {
                if ($component->getData() !== null) {
                    $isFeatureCollection = true;
                }
                $components[] = $this->getArray($component, false);
            }
            if (!$isFeatureCollection || !$isRoot) {
                return [
                        'type'       => 'GeometryCollection',
                        'geometries' => $components
                ];
            } else {
                $features = [];
                foreach ($geometry->getComponents() as $i => $component) {
                    $features[] = [
                            'type'       => 'Feature',
                            'properties' => $component->getData(),
                            'geometry'   => $components[$i],
                    ];
                }
                return [
                        'type'     => 'FeatureCollection',
                        'features' => $features
                ];
            }
        }

        if ($isRoot && $geometry->getData() !== null) {
            return [
                    'type'       => 'Feature',
                    'properties' => $geometry->getData(),
                    'geometry'   => [
                            'type'        => $geometry->geometryType(),
                            'coordinates' => $geometry->isEmpty() ? [] : $geometry->asArray()
                    ]
            ];
        }
        $object = [
                'type'        => $geometry->geometryType(),
                'coordinates' => $geometry->isEmpty() ? [] : $geometry->asArray()
        ];
        return $object;
    }

}
