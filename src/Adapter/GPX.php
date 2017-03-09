<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Collection;
use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;

/*
 * Copyright (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/GPX encoder/decoder
 */
class GPX implements GeoAdapter {
    /**
     * @var \DOMDocument
     */
    protected $xmlObject;

    private $namespace = false;
    private $nss = ''; // Name-space string. eg 'georss:'

    /**
     * Read GPX string into geometry objects
     *
     * @param string $gpx A GPX string
     *
     * @return Geometry|GeometryCollection
     */
    public function read($gpx) {
        return $this->geomFromText($gpx);
    }

    public function geomFromText($text) {
        // Change to lower-case and strip all CDATA
        $text = strtolower($text);
        $text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $text);

        // Load into DOMDocument
		//ignore error
		libxml_use_internal_errors(true);
		$xmObject = new \DOMDocument('1.0', 'UTF-8');
        @$xmObject->loadXML($text);
        if ($xmObject === false) {
            throw new \Exception("Invalid GPX: " . $text);
        }

        $this->xmlObject = $xmObject;
        try {
            $geom = $this->geomFromXML();
        } catch (\Exception $e) {
            throw new \Exception("Cannot Read Geometry From GPX: " . $text);
        }

        return $geom;
    }

    protected function geomFromXML() {
        /** @var Geometry[] $geometries */
        $geometries = array();
        $geometries = array_merge($geometries, $this->parseWaypoints());
        $geometries = array_merge($geometries, $this->parseTracks());
        $geometries = array_merge($geometries, $this->parseRoutes());

        if (empty($geometries)) {
            // TODO: use EmptyGeometryException
            return new GeometryCollection();
            //throw new \Exception("Invalid / Empty GPX");
        }

        return geoPHP::geometryReduce($geometries);
    }

    protected function childElements($xml, $nodeName = '') {
        $children = array();
        foreach ($xml->childNodes as $child) {
            if ($child->nodeName == $nodeName) {
                $children[] = $child;
            }
        }
        return $children;
    }

	/**
	 * @param \DOMElement $node
	 * @return Point
	 */
	protected function pointNode($node) {
		$lat = $node->attributes->getNamedItem("lat")->nodeValue;
		$lon = $node->attributes->getNamedItem("lon")->nodeValue;
		$elevation = null;
		$ele = $node->getElementsByTagName('ele');
		if ( $ele->length ) {
			$elevation = $ele->item(0)->nodeValue;
		}
		return new Point($lon, $lat, $elevation);
	}

    protected function parseWaypoints() {
        $points = array();
        $wpt_elements = $this->xmlObject->getElementsByTagName('wpt');
        foreach ($wpt_elements as $wpt) {
			$points[] = $this->pointNode($wpt);
        }
        return $points;
    }

    protected function parseTracks() {
        $lines = array();
        $trk_elements = $this->xmlObject->getElementsByTagName('trk');
        foreach ($trk_elements as $trk) {
            $components = array();
            /** @noinspection SpellCheckingInspection */
            foreach ($this->childElements($trk, 'trkseg') as $trackSegment) {
                /** @noinspection SpellCheckingInspection */
                foreach ($this->childElements($trackSegment, 'trkpt') as $trkpt) {
					/** @noinspection SpellCheckingInspection */
					$components[] = $this->pointNode($trkpt);
                }
            }
            if ($components) {
                $lines[] = new LineString($components);
            }
        }
        return $lines;
    }

    protected function parseRoutes() {
        $lines = array();
        $rte_elements = $this->xmlObject->getElementsByTagName('rte');
        foreach ($rte_elements as $rte) {
            $components = array();
            /** @noinspection SpellCheckingInspection */
            foreach ($this->childElements($rte, 'rtept') as $routePoint) {
                $lat = $routePoint->attributes->getNamedItem("lat")->nodeValue;
                $lon = $routePoint->attributes->getNamedItem("lon")->nodeValue;
                $components[] = new Point($lon, $lat);
            }
            $lines[] = new LineString($components);
        }
        return $lines;
    }


    /**
     * Serialize geometries into a GPX string.
     *
     * @param Geometry|GeometryCollection $geometry
     * @param bool $namespace
     * @return string The GPX string representation of the input geometries
     */
    public function write(Geometry $geometry, $namespace = false) {
        if ($namespace) {
            $this->namespace = $namespace;
            $this->nss = $namespace . ':';
        }
        return
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "<" . $this->nss . "gpx creator=\"geoPHP\" version=\"1.0\"\n" .
                "  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" \n" .
                "  xmlns=\"http://www.topografix.com/GPX/1/0\" \n" .
                "  xsi:schemaLocation=\"http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd\" >\n" .
                $this->geometryToGPX($geometry) .
                "</" . $this->nss . "gpx>\n";
    }

    /**
     * @param Geometry|Collection $geometry
     * @return string
     */
    protected function geometryToGPX($geometry) {
        if ($geometry->isEmpty()) {
            return null;
        }
        $type = strtolower($geometry->geometryType());
        switch ($type) {
            case 'point':
                return $this->pointToGPX($geometry);
            case 'linestring':
                /** @var LineString $geometry */
                return $this->linestringToGPX($geometry);
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return $this->collectionToGPX($geometry);
        }
        return '';
    }

    /**
     * @param Geometry $geom
     * @param bool $is_trackPoint Is geom a track point or way point
     * @return string
     */
	private function pointToGPX($geom, $is_trackPoint = false) {
		if ($geom->isEmpty()) {
			return '';
		}
		/** @noinspection SpellCheckingInspection */
		$tag = $is_trackPoint ? "trkpt" : "wpt";
		if ( $geom->hasZ() ) {
			$c = "\t\t<".$this->nss . $tag ." lat=\"".$geom->y()."\" lon=\"".$geom->x()."\">";
			$c .= "<ele>".$geom->z()."</ele>";
			$c .= "</".$this->nss. $tag . ">\n";
			return $c;
		}
		return "\t\t<".$this->nss. $tag . " lat=\"".$geom->y()."\" lon=\"".$geom->x()."\" />\n";
	}

	/**
	 * @param LineString $geom
	 * @return string
	 */
	private function linestringToGPX($geom) {
		/** @noinspection SpellCheckingInspection */
		$gpx = "<".$this->nss."trk>\n\t<".$this->nss."trkseg>\n";

		foreach ($geom->getComponents() as $comp) {
			$gpx .= $this->pointToGPX($comp, true);
		}

		/** @noinspection SpellCheckingInspection */
		$gpx .= "\t</".$this->nss."trkseg>\n</".$this->nss."trk>";

		return $gpx;
	}

    /**
     * @param Collection $geometry
     * @return string
     */
    public function collectionToGPX($geometry) {
        $gpx = '';
        $components = $geometry->getComponents();
        foreach ($components as $comp) {
            $gpx .= $this->geometryToGPX($comp);
        }

        return $gpx;
    }

}
