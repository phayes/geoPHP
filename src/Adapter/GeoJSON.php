<?php

namespace GeoPHP\Adapter;

use GeoPHP\Geometry\Geometry;
use GeoPHP\Geometry\GeometryCollection;
use GeoPHP\Geometry\LineString;
use GeoPHP\Geometry\MultiLineString;
use GeoPHP\Geometry\MultiPoint;
use GeoPHP\Geometry\MultiPolygon;
use GeoPHP\Geometry\Point;
use GeoPHP\Geometry\Polygon;
use GeoPHP\GeoPHP;

/**
 * GeoJSON class : a geojson reader/writer.
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJSON extends GeoAdapter
{
    /**
     * Given an object or a string, return a Geometry
     *
     * @param mixed $input The GeoJSON string or object
     * @return object Geometry
     */
    public function read($input)
    {
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
            $geoms = [];
            foreach ($input->features as $feature) {
                $geoms[] = $this->read($feature);
            }

            return GeoPHP::geometryReduce($geoms);
        }

        // Check to see if it's a Feature
        if ($input->type == 'Feature') {
            return $this->read($input->geometry);
        }

        // It's a geometry - process it
        return $this->objToGeom($input);
    }

    private function objToGeom($obj)
    {
        $type = $obj->type;

        if ($type == 'GeometryCollection') {
            return $this->objToGeometryCollection($obj);
        }
        $method = 'arrayTo' . $type;

        return $this->$method($obj->coordinates);
    }

    private function arrayToPoint($array)
    {
        if (!empty($array)) {
            return new Point($array[0], $array[1]);
        }

        return new Point();
    }

    private function arrayToLineString($array)
    {
        $points = [];
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }

        return new LineString($points);
    }

    private function arrayToPolygon($array)
    {
        $lines = [];
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }

        return new Polygon($lines);
    }

    private function arrayToMultiPoint($array)
    {
        $points = [];
        foreach ($array as $comp_array) {
            $points[] = $this->arrayToPoint($comp_array);
        }

        return new MultiPoint($points);
    }

    private function arrayToMultiLineString($array)
    {
        $lines = [];
        foreach ($array as $comp_array) {
            $lines[] = $this->arrayToLineString($comp_array);
        }

        return new MultiLineString($lines);
    }

    private function arrayToMultiPolygon($array)
    {
        $polys = [];
        foreach ($array as $comp_array) {
            $polys[] = $this->arrayToPolygon($comp_array);
        }

        return new MultiPolygon($polys);
    }

    private function objToGeometryCollection($obj)
    {
        $geoms = [];
        if (empty($obj->geometries)) {
            throw new \Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
        }
        foreach ($obj->geometries as $comp_object) {
            $geoms[] = $this->objToGeom($comp_object);
        }

        return new GeometryCollection($geoms);
    }

    /**
     * Serializes an object into a geojson string
     *
     * @param Geometry $obj The object to serialize
     * @return string The GeoJSON string
     */
    public function write(Geometry $geometry, $return_array = false)
    {
        if ($return_array) {
            return $this->getArray($geometry);
        }

        return json_encode($this->getArray($geometry));
    }

    public function getArray($geometry)
    {
        if ($geometry->getGeomType() == 'GeometryCollection') {
            $component_array = [];
            foreach ($geometry->components as $component) {
                $component_array[] = [
                    'type' => $component->geometryType(),
                    'coordinates' => $component->asArray(),
                ];
            }

            return [
                'type' => 'GeometryCollection',
                'geometries' => $component_array,
            ];
        }

        return [
            'type' => $geometry->getGeomType(),
            'coordinates' => $geometry->asArray(),
        ];
    }
}
