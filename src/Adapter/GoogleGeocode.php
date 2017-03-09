<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\MultiPolygon;

/*
 * (c) Camptocamp <info@camptocamp.com>
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Google Geocoder Adapter
 *
 *
 * @package    geoPHP
 * @author     Patrick Hayes <patrick.d.hayes@gmail.com>
 */
class GoogleGeocode implements GeoAdapter {
    /** @var \stdClass $result */
    protected $result;

    /**
     * Makes a geocoding (lat/lon lookup) with an address string or array geometry objects
     *
     * @param string $address Address to geocode
     * @param string $apiKey Your application's Google Maps Geocoding API key
     * @param string $return_type Type of Geometry to return. Can either be 'points' or 'bounds' (polygon)
     * @param array|bool|Geometry $bounds Limit the search area to within this region.
     *        For example by default geocoding "Cairo" will return the location of Cairo Egypt.
     *        If you pass a polygon of Illinois, it will return Cairo IL.
     * @param boolean $return_multiple - Return all results in a multipoint or multipolygon
     *
     * @return Geometry|GeometryCollection
     * @throws \Exception If geocoding fails
     */
    public function read($address, $apiKey=null, $return_type = 'point', $bounds = false, $return_multiple = false) {
        if (is_array($address)) {
            $address = join(',', $address);
        }

        if (gettype($bounds) == 'object') {
            $bounds = $bounds->getBBox();
        }
        if (gettype($bounds) == 'array') {
            $bounds_string = '&bounds=' . $bounds['miny'] . ',' . $bounds['minx'] . '|' . $bounds['maxy'] . ',' . $bounds['maxx'];
        } else {
            $bounds_string = '';
        }

        $url = "http://maps.googleapis.com/maps/api/geocode/json";
        $url .= '?address=' . urlencode($address);
        $url .= $bounds_string . ($apiKey ? '&key=' . $apiKey : '');
        $this->result = json_decode(@file_get_contents($url));

        if ($this->result->status == 'OK') {
            if (!$return_multiple) {
                if ($return_type == 'point') {
                    return $this->getPoint();
                }
                if ($return_type == 'bounds' || $return_type == 'polygon') {
                    return $this->getPolygon();
                }
            } else {
                if ($return_type == 'point') {
                    $points = array();
                    foreach ($this->result->results as $delta => $item) {
                        $points[] = $this->getPoint($delta);
                    }
                    return new MultiPoint($points);
                }
                if ($return_type == 'bounds' || $return_type == 'polygon') {
                    $polygons = array();
                    foreach ($this->result->results as $delta => $item) {
                        $polygons[] = $this->getPolygon($delta);
                    }
                    return new MultiPolygon($polygons);
                }
            }
        } else if ($this->result->status == 'ZERO_RESULTS') {
            return null;
        } else {
            if ($this->result->status) {
                throw new \Exception('Error in Google Reverse Geocoder: '
                        . $this->result->status
                        . (isset($this->result->error_message) ? '. '.$this->result->error_message : ''));
            } else {
                throw new \Exception('Unknown error in Google Reverse Geocoder');
            }
        }
        return false;
    }

    /**
     * Makes a Reverse Geocoding (address lookup) with the (center) point of Geometry
     * Detailed documentation of response values can be found in:
     * @see https://developers.google.com/maps/documentation/geocoding/intro#ReverseGeocoding
     *
     * @param Geometry $geometry
     * @param string $apiKey Your application's Google Maps Geocoding API key
     * @param string $return_type Should be either 'string' or 'array' or 'both'
     * @param string $language The language in which to return results. If not set, geocoder tries to use the native language of the domain.
     *
     * @return string|Object[]|null A formatted address or array of address components
     * @throws \Exception If geocoding fails
     */
    public function write(Geometry $geometry, $apiKey=null, $return_type = 'string', $language=null) {
        $centroid = $geometry->centroid();
        $lat = $centroid->y();
        $lon = $centroid->x();

        $url = "http://maps.googleapis.com/maps/api/geocode/json";
        /** @noinspection SpellCheckingInspection */
        $url .= '?latlng=' . $lat . ',' . $lon;
        $url .= ($language ? '&language=' . $language : '') . ($apiKey ? '&key=' . $apiKey : '');

        $this->result = json_decode(@file_get_contents($url));

        if ($this->result->status == 'OK') {
            if ($return_type == 'string') {
                return $this->result->results[0]->formatted_address;
            } else if ($return_type == 'array') {
                return $this->result->results[0]->address_components;
            } else if ($return_type == 'full') {
                return $this->result->results[0];

            }
        } else if ($this->result->status == 'ZERO_RESULTS') {
            if ($return_type == 'string') {
                return '';
            }
            if ($return_type == 'array') {
                return $this->result->results;
            }
        } else {
            if ($this->result->status) {
                throw new \Exception('Error in Google Reverse Geocoder: '
                        . $this->result->status
                        . (isset($this->result->error_message) ? '. '.$this->result->error_message : ''));
            } else {
                throw new \Exception('Unknown error in Google Reverse Geocoder');
            }
        }
        return false;
    }

    private function getPoint($delta = 0) {
        $lat = $this->result->results[$delta]->geometry->location->lat;
        $lon = $this->result->results[$delta]->geometry->location->lng;
        return new Point($lon, $lat);
    }

    private function getPolygon($delta = 0) {
        $points = array(
                $this->getTopLeft($delta),
                $this->getTopRight($delta),
                $this->getBottomRight($delta),
                $this->getBottomLeft($delta),
                $this->getTopLeft($delta),
        );
        $outer_ring = new LineString($points);
        return new Polygon(array($outer_ring));
    }

    private function getTopLeft($delta = 0) {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;
        return new Point($lon, $lat);
    }

    private function getTopRight($delta = 0) {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;
        return new Point($lon, $lat);
    }

    private function getBottomLeft($delta = 0) {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;
        return new Point($lon, $lat);
    }

    private function getBottomRight($delta = 0) {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;
        return new Point($lon, $lat);
    }
}
