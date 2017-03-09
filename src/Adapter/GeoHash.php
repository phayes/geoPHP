<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;

/**
 * PHP Geometry GeoHash encoder/decoder.
 *
 * @author prinsmc
 * @see http://en.wikipedia.org/wiki/Geohash
 *
 */
class GeoHash implements GeoAdapter {
    /**
     * @var string
     */
    /** @noinspection SpellCheckingInspection */
    public static $characterTable = "0123456789bcdefghjkmnpqrstuvwxyz";

    /**
     * array of neighbouring hash character maps.
     */
    private static $neighbours = array (
        // north
            'top' => array (
                    'even' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
                    'odd' => 'bc01fg45238967deuvhjyznpkmstqrwx'
            ),
        // east
            'right' => array (
                    'even' => 'bc01fg45238967deuvhjyznpkmstqrwx',
                    'odd' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy'
            ),
        // west
            'left' => array (
                    'even' => '238967debc01fg45kmstqrwxuvhjyznp',
                    'odd' => '14365h7k9dcfesgujnmqp0r2twvyx8zb'
            ),
        // south
            'bottom' => array (
                    'even' => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
                    'odd' => '238967debc01fg45kmstqrwxuvhjyznp'
            )
    );

    /**
     * array of bordering hash character maps.
     */
    private static $borders = array (
        // north
            'top' => array (
                    'even' => 'prxz',
                    'odd' => 'bcfguvyz'
            ),
        // east
            'right' => array (
                    'even' => 'bcfguvyz',
                    'odd' => 'prxz'
            ),
        // west
            'left' => array (
                    'even' => '0145hjnp',
                    'odd' => '028b'
            ),
        // south
            'bottom' => array (
                    'even' => '028b',
                    'odd' => '0145hjnp'
            )
    );

    /**
     * Convert the geoHash to a Point. The point is 2-dimensional.
     *
     * @param string $hash a GeoHash
     * @param boolean $as_grid Return the center point of hash grid or the grid cell as Polygon
     * @return Point the converted GeoHash
     */
    public function read($hash, $as_grid = false) {
        $decodedHash = $this->decode($hash);
        if (!$as_grid) {
            return new Point($decodedHash['centerLongitude'], $decodedHash['centerLatitude']);
        } else {
            return new Polygon(array(
                    new LineString(array(
                            new Point($decodedHash['minLongitude'], $decodedHash['maxLatitude']),
                            new Point($decodedHash['maxLongitude'], $decodedHash['maxLatitude']),
                            new Point($decodedHash['maxLongitude'], $decodedHash['minLatitude']),
                            new Point($decodedHash['minLongitude'], $decodedHash['minLatitude']),
                            new Point($decodedHash['minLongitude'], $decodedHash['maxLatitude']),
                    ))
            ));
        }
    }

    /**
     * Convert the geometry to geohash.
     *
     * @param Geometry $geometry
     * @param float|null $precision
     * @return string the GeoHash or null when the $geometry is not a Point
     */
    public function write(Geometry $geometry, $precision = null) {
        if ($geometry->isEmpty()) {
            return '';
        }

        if ($geometry->geometryType() === 'Point') {
            /** @var Point $geometry */
            return $this->encodePoint($geometry, $precision);
        } else {
            // The GeoHash is the smallest hash grid ID that fits the envelope
            $envelope = $geometry->envelope();
            $geoHashes = array();
            $geohash = '';
            foreach ($envelope->getPoints() as $point) {
                $geoHashes[] = $this->encodePoint($point, 0.0000001);
            }
            $i = 0;
            while ($i < strlen($geoHashes[0])) {
                $char = $geoHashes[0][$i];
                foreach ($geoHashes as $hash) {
                    if ($hash[$i] != $char) {
                        return $geohash;
                    }
                }
                $geohash .= $char;
                $i++;
            }
            return $geohash;
        }
    }

    /**
     * @author algorithm based on code by Alexander Songe <a@songe.me>
     * @see https://github.com/asonge/php-geohash/issues/1
     *
     * @param Point $point
     * @param float|null $precision
     * @return string The GeoHash
     * @throws \Exception
     */
    private function encodePoint($point, $precision = null) {
        $minLatitude = -90.0000000000001;
        $maxLatitude = 90.0000000000001;
        $minLongitude = -180.0000000000001;
        $maxLongitude = 180.0000000000001;
        $latitudeError = 90;
        $longitudeError = 180;
        $i = 0;
        $error = 180;
        $hash = '';

        if (!is_numeric($precision)) {
            $lap = strlen($point->y()) - strpos($point->y(), ".");
            $lop = strlen($point->x()) - strpos($point->x(), ".");
            $precision = pow(10, -max($lap - 1, $lop - 1, 0)) / 2;
        }

        if (
                $point->x() < $minLongitude || $point->y() < $minLatitude ||
                $point->x() > $maxLongitude || $point->y() > $maxLatitude
        ) {
            throw new \Exception("Point coordinates ({$point->x()}, {$point->y()}) are out of lat/lon range");
        }

        while ($error >= $precision) {
            $chr = 0;
            for ($b = 4; $b >= 0; --$b) {
                if ((1 & $b) == (1 & $i)) {
                    // even char, even bit OR odd char, odd bit...a lon
                    $next = ($minLongitude + $maxLongitude) / 2;
                    if ($point->x() > $next) {
                        $chr |= pow(2, $b);
                        $minLongitude = $next;
                    } else {
                        $maxLongitude = $next;
                    }
                    $longitudeError /= 2;
                } else {
                    // odd char, even bit OR even char, odd bit...a lat
                    $next = ($minLatitude + $maxLatitude) / 2;
                    if ($point->y() > $next) {
                        $chr |= pow(2, $b);
                        $minLatitude = $next;
                    } else {
                        $maxLatitude = $next;
                    }
                    $latitudeError /= 2;
                }
            }
            $hash .= self::$characterTable[$chr];
            $i++;
            $error = min($latitudeError, $longitudeError);
        }
        return $hash;
    }

    /**
     * @author algorithm based on code by Alexander Songe <a@songe.me>
     * @see https://github.com/asonge/php-geohash/issues/1
     *
     * @param string $hash a GeoHash
     * @return array Associative array.
     */
    private function decode($hash) {
        $result = array();
        $minLatitude = -90;
        $maxLatitude = 90;
        $minLongitude = -180;
        $maxLongitude = 180;
        $latitudeError = 90;
        $longitudeError = 180;
        for ($i = 0, $c = strlen($hash); $i < $c; $i++) {
            $v = strpos(self::$characterTable, $hash[$i]);
            if (1 & $i) {
                if (16 & $v) {
                    $minLatitude = ($minLatitude + $maxLatitude) / 2;
                } else {
                    $maxLatitude = ($minLatitude + $maxLatitude) / 2;
                }
                if (8 & $v) {
                    $minLongitude = ($minLongitude + $maxLongitude) / 2;
                } else {
                    $maxLongitude = ($minLongitude + $maxLongitude) / 2;
                }
                if (4 & $v) {
                    $minLatitude = ($minLatitude + $maxLatitude) / 2;
                } else {
                    $maxLatitude = ($minLatitude + $maxLatitude) / 2;
                }
                if (2 & $v) {
                    $minLongitude = ($minLongitude + $maxLongitude) / 2;
                } else {
                    $maxLongitude = ($minLongitude + $maxLongitude) / 2;
                }
                if (1 & $v) {
                    $minLatitude = ($minLatitude + $maxLatitude) / 2;
                } else {
                    $maxLatitude = ($minLatitude + $maxLatitude) / 2;
                }
                $latitudeError /= 8;
                $longitudeError /= 4;
            } else {
                if (16 & $v) {
                    $minLongitude = ($minLongitude + $maxLongitude) / 2;
                } else {
                    $maxLongitude = ($minLongitude + $maxLongitude) / 2;
                }
                if (8 & $v) {
                    $minLatitude = ($minLatitude + $maxLatitude) / 2;
                } else {
                    $maxLatitude = ($minLatitude + $maxLatitude) / 2;
                }
                if (4 & $v) {
                    $minLongitude = ($minLongitude + $maxLongitude) / 2;
                } else {
                    $maxLongitude = ($minLongitude + $maxLongitude) / 2;
                }
                if (2 & $v) {
                    $minLatitude = ($minLatitude + $maxLatitude) / 2;
                } else {
                    $maxLatitude = ($minLatitude + $maxLatitude) / 2;
                }
                if (1 & $v) {
                    $minLongitude = ($minLongitude + $maxLongitude) / 2;
                } else {
                    $maxLongitude = ($minLongitude + $maxLongitude) / 2;
                }
                $latitudeError /= 4;
                $longitudeError /= 8;
            }
        }
        $result['minLatitude'] = $minLatitude;
        $result['minLongitude'] = $minLongitude;
        $result['maxLatitude'] = $maxLatitude;
        $result['maxLongitude'] = $maxLongitude;
        $result['centerLatitude'] = round(($minLatitude + $maxLatitude) / 2, max(1, -round(log10($latitudeError))) - 1);
        $result['centerLongitude'] = round(($minLongitude + $maxLongitude) / 2, max(1, -round(log10($longitudeError))) - 1);
        return $result;
    }

    /**
     * Calculates the adjacent geohash of the geohash in the specified direction.
     * This algorithm is available in various ports that seem to point back to
     * geohash-js by David Troy under MIT notice.
     *
     *
     * @see https://github.com/davetroy/geohash-js
     * @see https://github.com/lyokato/objc-geohash
     * @see https://github.com/lyokato/libgeohash
     * @see https://github.com/masuidrive/pr_geohash
     * @see https://github.com/sunng87/node-geohash
     * @see https://github.com/davidmoten/geo
     *
     * @param string $hash the geohash (lowercase)
     * @param string $direction the direction of the neighbor (top, bottom, left or right)
     * @return string the geohash of the adjacent cell
     */
    public function adjacent($hash, $direction){
        $last = substr($hash, -1);
        $type = (strlen($hash) % 2)? 'odd': 'even';
        $base = substr($hash, 0, strlen($hash) - 1);
        if(strpos((self::$borders[$direction][$type]), $last) !== false){
            $base = $this->adjacent($base, $direction);
        }
        return $base.self::$characterTable[strpos(self::$neighbours[$direction][$type], $last)];
    }
}
