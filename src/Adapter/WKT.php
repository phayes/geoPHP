<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Collection;
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
 * WKT (Well Known Text) Adapter
 */
class WKT implements GeoAdapter {

    protected $hasZ      = false;
    protected $measured  = false;

    /**
     * Determines if the given typeString is a valid WKT geometry type
     *
     * @param string $typeString Type to find, eg. "Point", or "LineStringZ"
     * @return string|bool The geometry type if found or false
     */
    public static function isWktType($typeString) {
        foreach(geoPHP::getGeometryList() as $geom => $type) {
            if (strtolower((substr($typeString, 0, strlen($geom)))) == $geom) {
                return $type;
            }
        }
        return false;
    }

    /**
     * Read WKT string into geometry objects
     *
     * @param string $wkt A WKT string
     * @return Geometry
     * @throws \Exception
     */
    public function read($wkt) {
        $this->hasZ = false;
        $this->measured = false;

        $wkt = trim($wkt);
        $srid = NULL;
        // If it contains a ';', then it contains additional SRID data
        if (preg_match('#^srid=(\d+);#i', $wkt, $m)) {
            $srid = $m[1];
            $wkt = substr($wkt, strlen($m[0]));
        }

        // If geos is installed, then we take a shortcut and let it parse the WKT
        if (geoPHP::geosInstalled() ) {
            /** @noinspection PhpUndefinedClassInspection */
            $reader = new \GEOSWKTReader();
            try {
                /** @noinspection PhpUndefinedMethodInspection */
                $geom = geoPHP::geosToGeometry($reader->read($wkt));
                if ($srid) $geom->setSRID($srid);
                return $geom;
            } catch (\Exception $e) {
//                if ($e->getMessage() !== 'IllegalArgumentException: Empty Points cannot be represented in WKB') {
//                    throw $e;
//                } // else try with GeoPHP' parser
            }
        }

        if (  $geometry = $this->parseTypeAndGetData($wkt) ) {
            if ($geometry && $srid) {
                $geometry->setSRID($srid);
            }
            return $geometry;
        }
        throw new \Exception('Invalid Wkt');

    }

    /**
     * @param $wkt
     * @return Geometry|null
     */
    private function parseTypeAndGetData($wkt) {
        // geometry type is the first word
        if (preg_match('#^([a-z]*)#i', $wkt, $m)) {
            $geometryType = $this->isWktType($m[1]);

            $dataString = 'EMPTY';
            if ($geometryType) {
                if ( preg_match('#(z{0,1})(m{0,1})\s*\((.*)\)$#i', $wkt, $m) ) {
                    $this->hasZ 	= $m[1];
                    $this->measured = $m[2] ?: null;
                    $dataString = $m[3] ?: $dataString;
                }
            }

            $method = 'parse' . $geometryType;
            return call_user_func( [$this, $method], $dataString);
        }
        return null;
    }

    private function parsePoint($dataString) {
        // If it's marked as empty, then return an empty point
        if ($dataString == 'EMPTY') {
            return new Point();
        }
        $z = $m = null;
        $parts = explode(' ', trim($dataString));
        if (isset($parts[2])) {
            if ($this->measured) {
                $m = $parts[2];
            } else {
                $z = $parts[2];
            }
        }
        if (isset($parts[3])) {
            $m = $parts[3];
        }
        return new Point($parts[0], $parts[1], $z, $m);
    }

    private function parseLineString($data_string) {
        // If it's marked as empty, then return an empty line
        if ($data_string == 'EMPTY') {
            return new LineString();
        }

        $parts = explode(',',$data_string);
        $points = array();
        foreach ($parts as $part) {
            $points[] = $this->parsePoint($part);
        }
        return new LineString($points);
    }

    private function parsePolygon($data_string) {
        // If it's marked as empty, then return an empty polygon
        if ($data_string == 'EMPTY') {
            return new Polygon();
        }

        $lines = array();
        if ( preg_match_all('/\(([^)(]*)\)/', $data_string, $m) ) {
            $parts = $m[1];
            foreach ($parts as $part) {
                $lines[] = $this->parseLineString($part);
            }
        }
        return new Polygon($lines);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param string $data_string
     * @return MultiPoint
     */
    private function parseMultiPoint($data_string) {
        // If it's marked as empty, then return an empty MultiPoint
        if ($data_string == 'EMPTY') {
            return new MultiPoint();
        }

        $points = array();
        // Parse form: MULTIPOINT ((1 2), (3 4))
        if (  preg_match_all('/\((.*?)\)/', $data_string, $m) ) {
            $parts = $m[1];
            foreach ($parts as $part) {
                $points[] =  $this->parsePoint($part);
            }
        } else { // Parse form: MULTIPOINT (1 2, 3 4)
            foreach (explode(',', $data_string) as $part) {
                $points[] =  $this->parsePoint($part);
            }
        }
        return new MultiPoint($points);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param string $data_string
     * @return MultiLineString
     */
    private function parseMultiLineString($data_string) {
        // If it's marked as empty, then return an empty multi-linestring
        if ($data_string == 'EMPTY') {
            return new MultiLineString();
        }
        $lines = array();
        if (  preg_match_all('/\(([^\(].*?)\)/', $data_string, $m) ) {
            $parts = $m[1];
            foreach ($parts as $part) {
                $lines[] =  $this->parseLineString($part);
            }
        }
        return new MultiLineString($lines);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param string $data_string
     * @return MultiPolygon
     */
    private function parseMultiPolygon($data_string) {
        // If it's marked as empty, then return an empty multi-polygon
        if ($data_string == 'EMPTY') {
            return new MultiPolygon();
        }

        $polygons = array();
        if (  preg_match_all('/\(\(.*?\)\)/', $data_string, $m) ) {
            $parts = $m[0];
            foreach ($parts as $part) {
                $polygons[] =  $this->parsePolygon($part);
            }
        }
        return new MultiPolygon($polygons);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param string $data_string
     * @return GeometryCollection
     */
    private function parseGeometryCollection($data_string) {
        // If it's marked as empty, then return an empty geom-collection
        if ($data_string == 'EMPTY') {
            return new GeometryCollection();
        }

        $geometries = [];
        while (strlen($data_string) > 0) {
            if ($data_string[0] == ',') {
                $data_string = substr($data_string, 1);
            }
            preg_match('/\((?>[^()]+|(?R))*\)/i', $data_string, $m, PREG_OFFSET_CAPTURE);
            if (!isset($m[0])) {
                // something wired happened, we stop here before running to an infinite loop
                break;
            }
            $cutPosition = strlen($m[0][0]) + $m[0][1];
            $geometries[] = $this->parseTypeAndGetData(trim(substr($data_string, 0, $cutPosition)));
            $data_string = trim(substr($data_string, $cutPosition));
        }

        return new GeometryCollection($geometries);
    }


    /**
     * Serialize geometries into a WKT string.
     *
     * @param Geometry $geometry
     *
     * @return string The WKT string representation of the input geometries
     */
    public function write(Geometry $geometry) {
        // If geos is installed, then we take a shortcut and let it write the WKT
        if (geoPHP::geosInstalled()) {
            /** @noinspection PhpUndefinedClassInspection */
            $writer = new \GEOSWKTWriter();
            /** @noinspection PhpUndefinedMethodInspection */
            $writer->setRoundingPrecision(14);
            /** @noinspection PhpUndefinedMethodInspection */
            $writer->setTrim(TRUE);
            /** @noinspection PhpUndefinedMethodInspection */
            return $writer->write($geometry->getGeos());
        }
        $this->measured = $geometry->isMeasured();
        $this->hasZ     = $geometry->hasZ();

        if ($geometry->isEmpty()) {
            return strtoupper($geometry->geometryType()).' EMPTY';
        }

        if ($data = $this->extractData($geometry)) {
            $extension='';
            if(  $this->hasZ ) 	 $extension .= 'Z';
            if ( $this->measured ) $extension .= 'M';
            return strtoupper($geometry->geometryType()) . ($extension ? ' '.$extension :'') . ' ('.$data.')';
        }
        return '';
    }

    /**
     * Extract geometry to a WKT string
     *
     * @param Geometry|Collection $geometry A Geometry object
     *
     * @return string
     */
    public function extractData($geometry) {
        $parts = array();
        switch ($geometry->geometryType()) {
            case Geometry::POINT:
                $p = $geometry->x().' '.$geometry->y();
                if ( $geometry->hasZ() ) {
                    $p .= ' ' . $geometry->getZ();
                    $this->hasZ = $this->hasZ || $geometry->hasZ();
                }
                if ( $geometry->isMeasured() ) {
                    $p .= ' ' . $geometry->getM();
                    $this->measured = $this->measured || $geometry->isMeasured();
                }
                return $p;
            case Geometry::LINE_STRING:
                foreach ($geometry->getComponents() as $component) {
                    $parts[] = $this->extractData($component);
                }
                return implode(', ', $parts);
            case Geometry::POLYGON:
            case Geometry::MULTI_POINT:
            case Geometry::MULTI_LINE_STRING:
            case Geometry::MULTI_POLYGON:
                foreach ($geometry->getComponents() as $component) {
                    $parts[] = '(' . $this->extractData($component) . ')';
                }
                return implode(', ', $parts);
            case Geometry::GEOMETRY_COLLECTION:
                foreach ($geometry->getComponents() as $component) {
                    $this->hasZ = $this->hasZ || $geometry->hasZ();
                    $this->measured = $this->measured || $geometry->isMeasured();

                    $extension='';
                    if ( $this->hasZ ) {
                        $extension .= 'Z';
                    }
                    if ( $this->measured ) {
                        $extension .= 'M';
                    }
                    $parts[] = strtoupper($component->geometryType()). ($extension ? ' '.$extension : '') .' ('.$this->extractData($component).')';
                }
                return implode(', ', $parts);
        }
        return '';
    }
}

