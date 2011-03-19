<?php
/*
 * Copyright (c) Patrick Hayes
 * Copyright (c) 2010-2011, Arnaud Renevier
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/KML encoder/decoder
 *
 * Mainly inspired/adapted from OpenLayers( http://www.openlayers.org ) 
 *   Openlayers/format/WKT.js
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 */
class KML extends GeoAdapter
{

  /**
   * Read KML string into geometry objects
   *
   * @param string $kml A KML string
   *
   * @return Geometry|GeometryCollection
   */
  public function read($kml)
  {
    return $this->geomFromText($kml);
  }

  /**
   * Serialize geometries into a KML string.
   *
   * @param Geometry $geometry
   *
   * @return string The KML string representation of the input geometries
   */
  public function write(Geometry $geometry)
  {
    return $this->geometryToKML($geometry);
  }
  
  static public function geomFromText($text) {
        if (!function_exists("simplexml_load_string") || !function_exists("libxml_use_internal_errors")) {
            throw new UnavailableResource("simpleXML");
        }
        $ltext = strtolower($text);
        libxml_use_internal_errors(true);
        $xmlobj = simplexml_load_string($ltext);
        if ($xmlobj === false) {
            throw new Exception("Invalid KML: ". $text);
        }

        try {
            $geom = static::_geomFromXML($xmlobj);
        } catch(InvalidText $e) {
            throw new Exception("Invalid KML: ". $text);
        } catch(\Exception $e) {
            throw $e;
        }

        return $geom;
    }

    static protected function childElements($xml, $nodename = "") {
        $nodename = strtolower($nodename);
        $res = array();
        foreach ($xml->children() as $child) {
            if ($nodename) {
                if (strtolower($child->getName()) == $nodename) {
                    array_push($res, $child);
                }
            } else {
                array_push($res, $child);
            }
        }
        return $res;
    }

    static protected function _childsCollect($xml) {
        $components = array();
        foreach (static::childElements($xml) as $child) {
            try {
                $geom = static::_geomFromXML($child);
                $components[] = $geom;
            } catch(InvalidText $e) {
            }
        }

        $ncomp = count($components);
        if ($ncomp == 0) {
            throw new Exception("Invalid KML");
        } else if ($ncomp == 1) {
            return $components[0];
        } else {
            return new GeometryCollection($components);
        }
    }

    static protected function parsePoint($xml) {
        $coordinates = static::_extractCoordinates($xml);
        $coords = preg_split('/,/', (string)$coordinates[0]);
        return array_map("trim", $coords);
    }

    static protected function parseLineString($xml) {
        $coordinates = static::_extractCoordinates($xml);
        foreach (preg_split('/\s+/', trim((string)$coordinates[0])) as $compstr) {
            $coords = preg_split('/,/', $compstr);
            $components[] = new Point($coords[0],$coords[1]);
        }
        return $components;
    }

    static protected function parseLinearRing($xml) {
        return static::parseLineString($xml);
    }

    static protected function parsePolygon($xml) {
        $ring = array();
        foreach (static::childElements($xml, 'outerboundaryis') as $elem) {
            $ring = array_merge($ring, static::childElements($elem, 'linearring'));
        }

        if (count($ring) != 1) {
            throw new Exception("Invalid KML");
        }

        $components = array(new LinearRing(static::parseLinearRing($ring[0])));
        foreach (static::childElements($xml, 'innerboundaryis') as $elem) {
            foreach (static::childElements($elem, 'linearring') as $ring) {
                $components[] = new LinearRing(static::parseLinearRing($ring[0]));
            }
        }
        return $components;
    }

    static protected function parseMultiGeometry($xml) {
        $components = array();
        foreach ($xml->children() as $child) {
            $components[] = static::_geomFromXML($child);
        }
        return $components;
    }

    static protected function _extractCoordinates($xml) {
        $coordinates = static::childElements($xml, 'coordinates');
        if (count($coordinates) != 1) {
            throw new Exception("Invalid KML");
        }
        return $coordinates;
    }

    static protected function _geomFromXML($xml) {
        $nodename = strtolower($xml->getName());
        if ($nodename == "kml" or $nodename == "placemark") {
            return static::_childsCollect($xml);
        }

        foreach (array("Point", "LineString", "LinearRing", "Polygon", "MultiGeometry") as $kml_type) {
            if (strtolower($kml_type) == $nodename) {
                $type = $kml_type;
                break;
            }
        }

        if (!isset($type)) {
            throw new Exception("Invalid KML");
        }

        try {
            $components = call_user_func(array('static', 'parse'.$type), $xml);
        } catch(InvalidText $e) {
            throw new Exception("Invalid KML");
        } catch(\Exception $e) {
            throw $e;
        }

        if ($type == "MultiGeometry") {
            if (count($components)) {
                $possibletype = $components[0]::name;
                $sametype = true;
                foreach (array_slice($components, 1) as $component) {
                    if ($component::name != $possibletype) {
                        $sametype = false;
                        break;
                    }
                }
                if ($sametype) {
                    switch ($possibletype) {
                        case "Point":
                            return new MultiPoint($components);
                        break;
                        case "LineString":
                            return new MultiLineString($components);
                        break;
                        case "Polygon":
                            return new MultiPolygon($components);
                        break;
                        default:
                        break;
                    }
                }
            }
            return new GeometryCollection($components);
        }

        $constructor = __NAMESPACE__ . '\\' . $type;
        return new $constructor($components);
    }
    
    private function geometryToKML($geom) {
      $type = strtolower($geom->getGeomType());
      switch ($type) {
          case 'point':
              return $this->pointToKML($geom);
              break;
          case 'linestring':
          case 'linearring':
              return $this->linestringToKML($geom);
              break;
          case 'polygon':
              return $this->polygonToKML($geom);
              break;
          case 'multipoint':
          case 'multilinestring':
          case 'multipolygon':
          case 'geometrycollection':
              return $this->collectionToKML($geom);
              break;
      }
    }

    private function pointToKML($geom) {
      return "<point><coordinates>".$geom->getX().",".$geom->getY()."</coordinates></point>";
    }

    private function linestringToKML($geom) {
        $type = strtolower($geom->getGeomType());
        return "<" . $type . "><coordinates>" . implode(" ", array_map(function($comp) {
                    return $comp->getX().",".$comp->getY();
                }, $geom->getComponents())). "</coordinates></" . $type . ">";
    }

    public function polygonToKML($geom) {
        $componenets = $geom->getComponents();
        $str = '<outerBoundaryIs>' . $this->linestringToKML($componenets[0]) . '</outerBoundaryIs>';
        
        $str .= implode("", array_map(function($comp) {
            return '<innerBoundaryIs>' . $this->linestringToKML($comp) . '</innerBoundaryIs>';
        }, array_slice($componenets, 1)));
        
        return '<polygon>' . $str . '</polygon>';
    }
    
    public function collectionToKML($geom) {
      $componenets = $geom->getComponents();
      return '<MultiGeometry>' . implode("", array_map(function($comp) {
        $sub_adapter = new KML();
        return $sub_adapter->write($comp);
      }, $componenets)) . '</MultiGeometry>';
    }

}
