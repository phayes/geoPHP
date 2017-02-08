<?php

namespace Phayes\GeoPHP\Adapters;

use Phayes\GeoPHP\Geometry\Point;
use Phayes\GeoPHP\Geometry\Polygon;
use Phayes\GeoPHP\Geometry\LineString;
use Phayes\GeoPHP\Geometry\MultiPoint;
use Phayes\GeoPHP\Geometry\MultiPolygon;
use Phayes\GeoPHP\Geometry\Geometry;
use Phayes\GeoPHP\Geometry\GeometryCollection;
use Exception;

/**
 * PHP Google Geocoder Adapter
 *
 *
 * @package    geoPHP
 * @author     Patrick Hayes <patrick.d.hayes@gmail.com>
 */
class GoogleGeocode extends GeoAdapter
{
    /** @var array|object */
    private $result;

    /**
     * Read an address string or array geometry objects
     *
     * @param string              $address         - Address to geocode
     * @param string              $return_type     - Type of Geometry to return. Can either be 'points' or 'bounds'
     *                                             (polygon)
     * @param array|bool|Geometry $bounds          - Limit the search area to within this region. For example
     *                                             by default geocoding "Cairo" will return the location of Cairo
     *                                             Egypt.
     *                                             If you pass a polygon of illinois, it will return Cairo IL.
     * @param bool                $return_multiple - Return all results in a multipoint or multipolygon
     *
     * @return bool|Geometry|GeometryCollection
     * @throws Exception
     */
    public function read($address, $return_type = 'point', $bounds = false, $return_multiple = false)
    {
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
        $url .= $bounds_string;
        $url .= '&sensor=false';
        $this->result = json_decode(@file_get_contents($url));
        if ($this->result->status == 'OK') {
            if ($return_multiple == false) {
                if ($return_type == 'point') {
                    return $this->getPoint();
                }
                if ($return_type == 'bounds' || $return_type == 'polygon') {
                    return $this->getPolygon();
                }
            }
            if ($return_multiple == true) {
                if ($return_type == 'point') {
                    $points = [];
                    foreach ($this->result->results as $delta => $item) {
                        $points[] = $this->getPoint($delta);
                    }

                    return new MultiPoint($points);
                }
                if ($return_type == 'bounds' || $return_type == 'polygon') {
                    $polygons = [];
                    foreach ($this->result->results as $delta => $item) {
                        $polygons[] = $this->getPolygon($delta);
                    }

                    return new MultiPolygon($polygons);
                }
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Geocoder: ' . $this->result->status);
            } else {
                throw new Exception('Unknown error in Google Geocoder');
            }
        }
    }


    /**
     * Serialize geometries into a WKT string.
     *
     * @param Geometry $geometry
     * @param string   $return_type Should be either 'string' or 'array'
     *
     * @return string Does a reverse geocode of the geometry
     * @throws Exception
     */
    public function write(Geometry $geometry, $return_type = 'string')
    {
        $centroid = $geometry->getCentroid();
        $lat = $centroid->getY();
        $lon = $centroid->getX();
        $url = "http://maps.googleapis.com/maps/api/geocode/json";
        $url .= '?latlng=' . $lat . ',' . $lon;
        $url .= '&sensor=false';
        $this->result = json_decode(@file_get_contents($url));
        if ($this->result->status == 'OK') {
            if ($return_type == 'string') {
                return $this->result->results[0]->formatted_address;
            }
            if ($return_type == 'array') {
                return $this->result->results[0]->address_components;
            }
        } elseif ($this->result->status == 'ZERO_RESULTS') {
            if ($return_type == 'string') {
                return '';
            }
            if ($return_type == 'array') {
                return $this->result->results;
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Reverse Geocoder: ' . $this->result->status);
            }
            throw new Exception('Unknown error in Google Reverse Geocoder');
        }
    }

    private function getPoint($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->location->lat;
        $lon = $this->result->results[$delta]->geometry->location->lng;

        return new Point($lon, $lat);
    }


    private function getPolygon($delta = 0)
    {
        $points = [
            $this->getTopLeft($delta),
            $this->getTopRight($delta),
            $this->getBottomRight($delta),
            $this->getBottomLeft($delta),
            $this->getTopLeft($delta),
        ];
        $outer_ring = new LineString($points);

        return new Polygon([$outer_ring]);
    }

    private function getTopLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getTopRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }

    private function getBottomLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getBottomRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }
}
