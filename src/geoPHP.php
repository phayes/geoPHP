<?php
/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace geoPHP;

use geoPHP\Adapter\GeoHash;
use geoPHP\Geometry\Collection;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;

class geoPHP {

    static function version() {
        return '2.0-dev';
    }

    // Earth radius constants in meters

    /** WGS84 semi-major axis (a), aka equatorial radius */
    const EARTH_WGS84_SEMI_MAJOR_AXIS = 6378137.0;
    /** WGS84 semi-minor axis (b), aka polar radius */
    const EARTH_WGS84_SEMI_MINOR_AXIS = 6356752.314245;
    /** WGS84 inverse flattening */
    const EARTH_WGS84_FLATTENING      = 298.257223563;

    /** WGS84 semi-major axis (a), aka equatorial radius */
    const EARTH_GRS80_SEMI_MAJOR_AXIS = 6378137.0;
    /** GRS80 semi-minor axis */
    const EARTH_GRS80_SEMI_MINOR_AXIS = 6356752.314140;
    /** GRS80 inverse flattening */
    const EARTH_GRS80_FLATTENING      = 298.257222100882711;

    /** IUGG mean radius R1 = (2a + b) / 3 */
    const EARTH_MEAN_RADIUS           = 6371008.8;
    /** IUGG R2: Earth's authalic ("equal area") radius is the radius of a hypothetical perfect sphere
     * which has the same surface area as the reference ellipsoid. */
    const EARTH_AUTHALIC_RADIUS       = 6371007.2;

    const CLASS_NAMESPACE = 'geoPHP\\';

    private static $adapterMap = [
            'wkt'            => 'WKT',
            'ewkt'           => 'EWKT',
            'wkb'            => 'WKB',
            'ewkb'           => 'EWKB',
            'json'           => 'GeoJSON',
            'geojson'        => 'GeoJSON',
            'kml'            => 'KML',
            'gpx'            => 'GPX',
            'georss'         => 'GeoRSS',
            'google_geocode' => 'GoogleGeocode',
            'geohash'        => 'GeoHash',
            'twkb'           => 'TWKB',
            'osm'            => 'OSM',
    ];

    public static function getAdapterMap() {
        return self::$adapterMap;
    }

    private static $geometryList = [
            'point'              => 'Point',
            'linestring'         => 'LineString',
            'polygon'            => 'Polygon',
            'multipoint'         => 'MultiPoint',
            'multilinestring'    => 'MultiLineString',
            'multipolygon'       => 'MultiPolygon',
            'geometrycollection' => 'GeometryCollection',
    ];

    public static function getGeometryList() {
        return self::$geometryList;
    }

    /**
     * Converts data to Geometry using geo adapters
     *
     * If $data is an array, all passed in values will be combined into a single geometry
     *
     * @param mixed $data The data in any supported format, including geoPHP Geometry
     * @var null|string $type Data type. Tries to detect if omitted
     * @var mixed|null $other_args Arguments will be passed to the geo adapter
     *
     * @return Collection|Geometry
     * @throws \Exception
     */
    static function load($data) {
        $args = func_get_args();

        $data = array_shift($args);
        $type = count($args) && @array_key_exists($args[0], self::$adapterMap) ? strtolower(array_shift($args)) : null;

        // Auto-detect type if needed
        if (!$type) {
            // If the user is trying to load a Geometry from a Geometry... Just pass it back
            if (is_object($data)) {
                if ($data instanceOf Geometry) {
                    return $data;
                }
            }

            $detected = geoPHP::detectFormat($data);
            if (!$detected) {
                throw new \Exception("Can not detect format");
            }
            $format = explode(':', $detected);
            $type = array_shift($format);
            $args = $format ?: $args;
        }

        if (!array_key_exists($type, self::$adapterMap)) {
            throw new \Exception('geoPHP could not find an adapter of type ' . htmlentities($type));
        }
        $adapterType = self::CLASS_NAMESPACE . 'Adapter\\' . self::$adapterMap[$type];

        $adapter = new $adapterType();

        // Data is not an array, just pass it normally
        if (!is_array($data)) {
            $result = call_user_func_array([$adapter, "read"], array_merge([$data], $args));
        } // Data is an array, combine all passed in items into a single geometry
        else {
            $geometries = [];
            foreach ($data as $item) {
                $geometries[] = call_user_func_array([$adapter, "read"], array_merge($item, $args));
            }
            $result = geoPHP::buildGeometry($geometries);
        }

        return $result;
    }

    static function geosInstalled($force = null) {
        static $geos_installed = null;
        if ($force !== null) {
            $geos_installed = $force;
        }
        if (getenv('GEOS_DISABLED') == 1) {
            $geos_installed = false;
        }
        if ($geos_installed !== null) {
            return $geos_installed;
        }
        $geos_installed = class_exists('GEOSGeometry', false);

        return $geos_installed;
    }

    /**
     * @param $geos
     * @return Geometry|null
     * @throws \Exception
     */
    static function geosToGeometry($geos) {
        if (!geoPHP::geosInstalled()) {
            return null;
        }
		/** @noinspection PhpUndefinedClassInspection */
        $wkb_writer = new \GEOSWKBWriter();
		/** @noinspection PhpUndefinedMethodInspection */
        $wkb = $wkb_writer->writeHEX($geos);
        $geometry = geoPHP::load($wkb, 'wkb', true);
        if ($geometry) {
            $geometry->setGeos($geos);
            return $geometry;
        }

        return null;
    }

    /**
     * Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
     * For example a GeometryCollection of only points will become a MultiPoint
     * A multi-point containing a single point will return a point.
     * An array of geometries can be passed and they will be compiled into a single geometry
     *
     * @param Geometry|Geometry[]|GeometryCollection|GeometryCollection[] $geometries
     * @return bool|GeometryCollection
     */
    public static function geometryReduce($geometries) {
        if (empty($geometries)) {
            return false;
        }
        /*
         * If it is a single geometry
         */
        if ($geometries instanceof Geometry) {
            /** @var Geometry|GeometryCollection $geometries */
            // If the geometry cannot even theoretically be reduced more, then pass it back
            $single_geometries = ['Point', 'LineString', 'Polygon'];
            if (in_array($geometries->geometryType(), $single_geometries)) {
                return $geometries;
            }

            // If it is a multi-geometry, check to see if it just has one member
            // If it does, then pass the member, if not, then just pass back the geometry
            if (strpos($geometries->geometryType(), 'Multi') === 0) {
                $components = $geometries->getComponents();
                if (count($components) == 1) {
                    return $components[0];
                } else {
                    return $geometries;
                }
            }
        } else if (is_array($geometries) && count($geometries) == 1) {
            // If it's an array of one, then just parse the one
            return geoPHP::geometryReduce(array_shift($geometries));
        }

        if (!is_array($geometries)) {
            $geometries = [$geometries];
        }
        /**
         * So now we either have an array of geometries
         * @var Geometry[]|GeometryCollection[] $geometries
         */

        $reducedGeometries = [];
        $geometryTypes = [];
        self::_explodeCollections($geometries, $reducedGeometries, $geometryTypes);

        $geometryTypes = array_unique($geometryTypes);
        if (empty($geometryTypes)) {
            return false;
        }
        if (count($geometryTypes) == 1) {
            if (count($reducedGeometries) == 1) {
                return $reducedGeometries[0];
            } else {
                $class = self::CLASS_NAMESPACE . 'Geometry\\' . (strstr($geometryTypes[0], 'Multi') ? '' : 'Multi')  . $geometryTypes[0];
                return new $class($reducedGeometries);
            }
        } else {
            return new GeometryCollection($reducedGeometries);
        }
    }

    /**
     * @param Geometry[]|GeometryCollection[] $unreduced
     */
    private static function _explodeCollections($unreduced, &$reduced, &$types) {
        foreach ($unreduced as $item) {
            if ($item->geometryType() == 'GeometryCollection' || strpos($item->geometryType(), 'Multi') === 0) {
                self::_explodeCollections($item->getComponents(), $reduced, $types);
            } else {
                $reduced[] = $item;
                $types[] = $item->geometryType();
            }
        }
    }

    /**
     * Build an appropriate Geometry, MultiGeometry, or GeometryCollection to contain the Geometries in it.
     *
     * @see geos::geom::GeometryFactory::buildGeometry
     *
     * @param Geometry|Geometry[]|GeometryCollection|GeometryCollection[] $geometries
     * @return GeometryCollection|null A Geometry of the "smallest", "most type-specific" class that can contain the elements.
     */
    public static function buildGeometry($geometries) {
        if (empty($geometries)) {
            return new GeometryCollection();
        }

        /*
         * If it is a single geometry
         */
        if ($geometries instanceof Geometry) {
            return $geometries;
        } else if (!is_array($geometries)) {
            return null;
        } else if (count($geometries) == 1) {
            // If it's an array of one, then just parse the one
            return geoPHP::buildGeometry(array_shift($geometries));
        }

        /**
         * So now we either have an array of geometries
         * @var Geometry[]|GeometryCollection[] $geometries
         */

        $geometryTypes = [];
        foreach ($geometries as $item) {
            if ($item) {
                $geometryTypes[] = $item->geometryType();
            }
        }
        $geometryTypes = array_unique($geometryTypes);
        if (empty($geometryTypes)) {
            return null;
        }
        if (count($geometryTypes) == 1) {
            if ($geometryTypes[0] === Geometry::GEOMETRY_COLLECTION) {
                return new GeometryCollection($geometries);
            }
            if (count($geometries) == 1) {
                return $geometries[0];
            } else {
                $newType = (strpos($geometryTypes[0], 'Multi') !== false ? '' : 'Multi') . $geometryTypes[0];
                foreach ($geometries as $geometry) {
                    if ($geometry->isEmpty()) {
                        return new GeometryCollection($geometries);
                    }
                }
                $class = self::CLASS_NAMESPACE . 'Geometry\\' . $newType;
                return new $class($geometries);
            }
        }
        return new GeometryCollection($geometries);
    }

    // Detect a format given a value. This function is meant to be SPEEDY.
    // It could make a mistake in XML detection if you are mixing or using namespaces in weird ways (ie, KML inside an RSS feed)
    public static function detectFormat(&$input) {
        $mem = fopen('php://memory', 'x+');
        fwrite($mem, $input, 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
        fseek($mem, 0);

        $bin = fread($mem, 11);
        $bytes = unpack("c*", $bin);

        // If bytes is empty, then we were passed empty input
        if (empty($bytes)) {
            return false;
        }

        // First char is a tab, space or carriage-return. trim it and try again
        if ($bytes[1] == 9 || $bytes[1] == 10 || $bytes[1] == 32) {
            $input = ltrim($input);
            return geoPHP::detectFormat($input);
        }

        // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
        if ($bytes[1] == 1 || $bytes[1] == 0) {
            $wkbType = current(unpack($bytes[1] == 1 ? 'V' : 'N', substr($bin, 1, 4)));
            if (array_search($wkbType & 0xF, Adapter\WKB::$typeMap)) {
                // If SRID byte is TRUE (1), it's EWKB
                if ($wkbType & Adapter\WKB::SRID_MASK == Adapter\WKB::SRID_MASK) {
                    return 'ewkb';
                } else {
                    return 'wkb';
                }
            }
        }

        // Detect HEX encoded WKB or EWKB (PostGIS format) -- first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
        // The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
        // This differentiates it from a geohash, which is always shorter than 13 characters.
        if ($bytes[1] == 48 && ($bytes[2] == 49 || $bytes[2] == 48) && strlen($input) > 12) {
            if ((current(unpack($bytes[2] == 49 ? 'V' : 'N', hex2bin(substr($bin, 2, 8)))) & Adapter\WKB::SRID_MASK) == Adapter\WKB::SRID_MASK) {
                return 'ewkb:true';
            } else {
                return 'wkb:true';
            }
        }

        // Detect GeoJSON - first char starts with {
        if ($bytes[1] == 123) {
            return 'json';
        }

        // Detect EWKT - strats with "SRID=number;"
        if (substr($input, 0, 5) === 'SRID=') {
            return 'ewkt';
        }

        // Detect WKT - starts with a geometry type name
        if (Adapter\WKT::isWktType(strstr($input, ' ', true))) {
            return 'wkt';
        }

        // Detect XML -- first char is <
        if ($bytes[1] == 60) {
            // grab the first 1024 characters
            $string = substr($input, 0, 1024);
            if (strpos($string, '<kml') !== false) {
                return 'kml';
            }
            if (strpos($string, '<coordinate') !== false) {
                return 'kml';
            }
            if (strpos($string, '<gpx') !== false) {
                return 'gpx';
            }
            if (strpos($string, '<osm ') !== false) {
                return 'osm';
            }
            if (preg_match('/<[a-z]{3,20}>/', $string) !== false) {
                return 'georss';
            }
        }

        // We need an 8 byte string for geohash and unpacked WKB / WKT
        fseek($mem, 0);
        $string = trim(fread($mem, 8));

        // Detect geohash - geohash ONLY contains lowercase chars and numerics
        preg_match('/['.GeoHash::$characterTable.']+/', $string, $matches);
        if (isset($matches[0]) && $matches[0] == $string && strlen($input) <= 13) {
            return 'geohash';
        }

        preg_match('/^[a-f0-9]+$/', $string, $matches);
        if (isset($matches[0])) {
            return 'twkb:true';
        } else {
            return 'twkb';
        }

        // What do you get when you cross an elephant with a rhino?
        // http://youtu.be/RCBn5J83Poc
    }

}
