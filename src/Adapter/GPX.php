<?php

namespace geoPHP\Adapter;

use DOMDocument;
use DOMElement;
use geoPHP\Geometry\Collection;
use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiLineString;

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

	protected $nss = ''; // Name-space string. eg 'georss:'

	/**
	 * @var GpxTypes
	 */
	protected $gpxTypes;

	/**
	 * @var \DOMXPath
	 */
	protected $xpath;

	protected $parseGarminRpt = false;
	protected $trackFromRoute = null;

    /**
     * Read GPX string into geometry object
     *
     * @param string $gpx A GPX string
	 * @param array|null $allowedElements Which elements can be read from each GPX type
	 *                   If not specified, every element defined in the GPX specification can be read
	 *                   Can be overwritten with an associative array, with type name in keys.
	 *                   eg.: ['wptType' => ['ele', 'name'], 'trkptType' => ['ele'], 'metadataType' => null]
     * @return Geometry|GeometryCollection
	 * @throws \Exception If GPX is not a valid XML
     */
    public function read($gpx, $allowedElements = null) {
		$this->gpxTypes = new GpxTypes($allowedElements);

		//libxml_use_internal_errors(true); // why?

        // Load into DOMDocument
        $xmlObject = new DOMDocument('1.0', 'UTF-8');
		$xmlObject->preserveWhiteSpace = false;
        @$xmlObject->loadXML($gpx);
        if ($xmlObject === false) {
            throw new \Exception("Invalid GPX: " . $gpx);
        }

		$this->parseGarminRpt = strpos($gpx, 'gpxx:rpt') > 0;

		// Initialize XPath parser if needed (currently only for Garmin extensions)
		if ($this->parseGarminRpt) {
			$this->xpath = new \DOMXPath($xmlObject);
			$this->xpath->registerNamespace('gpx', 'http://www.topografix.com/GPX/1/1');
			$this->xpath->registerNamespace('gpxx', 'http://www.garmin.com/xmlschemas/GpxExtensions/v3');
		}

        try {
            $geom = $this->geomFromXML($xmlObject);
            if ($geom->isEmpty()) {
                /* Geometry was empty but maybe because its tags was not lower cased.
                   We try to lower-case tags and try to run again, but just once.
                */
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
                $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : null;
                if ($caller && $caller !== __FUNCTION__) {
                    $gpx = preg_replace_callback("/(<\/?\w+)(.*?>)/", function ($m) {
                        return strtolower($m[1]) . $m[2];
                    }, $gpx);
                    $geom = $this->read($gpx, $allowedElements);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Cannot Read Geometry From GPX: " . $gpx);
        }

        return $geom;
    }

	/**
	 * Parses the GPX XML and returns a geometry
	 * @param DOMDocument $xmlObject
	 * @return GeometryCollection|Geometry Returns the geometry representation of the GPX (@see geoPHP::buildGeometry)
	 */
    protected function geomFromXML($xmlObject) {
        /** @var Geometry[] $geometries */
        $geometries = array_merge(
                $this->parseWaypoints($xmlObject),
				$this->parseTracks($xmlObject),
				$this->parseRoutes($xmlObject)
        );

		if (isset($this->trackFromRoute)) {
			$trackFromRoute = new LineString($this->trackFromRoute);
			$trackFromRoute->setData('gpxType', 'track');
			$trackFromRoute->setData('type', 'planned route');
			$geometries[] = $trackFromRoute;
		}

		$geometry = geoPHP::buildGeometry($geometries);
		if (in_array('metadata', $this->gpxTypes->get('gpxType')) && $xmlObject->getElementsByTagName('metadata')->length === 1) {
			$metadata = self::parseNodeProperties(
					$xmlObject->getElementsByTagName('metadata')->item(0), $this->gpxTypes->get('metadataType')
			);
			if ($geometry->getData() !== null && $metadata !== null) {
				$geometry = new GeometryCollection([$geometry]);
			}
			$geometry->setData($metadata);
		}

		return $geometry;
    }

    protected function childElements($xml, $nodeName = '') {
        $children = [];
        foreach ($xml->childNodes as $child) {
            if ($child->nodeName == $nodeName) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * @param DOMElement $node
     * @return Point
     */
    protected function parsePoint($node) {
        $lat = $node->attributes->getNamedItem("lat")->nodeValue;
        $lon = $node->attributes->getNamedItem("lon")->nodeValue;
        $elevation = null;
        $ele = $node->getElementsByTagName('ele');
        if ( $ele->length ) {
            $elevation = $ele->item(0)->nodeValue;
        }
		$point = new Point($lon, $lat, $elevation);
		$point->setData($this->parseNodeProperties($node, $this->gpxTypes->get($node->nodeName . 'Type')));
		if ($node->nodeName === 'rtept' && $this->parseGarminRpt) {
			foreach($this->xpath->query('.//gpx:extensions/gpxx:RoutePointExtension/gpxx:rpt', $node) as $element) {
				$this->trackFromRoute[] = $this->parsePoint($element);
			}
		}
		return $point;
    }

	/**
	 * @param DOMDocument $xmlObject
	 * @return Point[]
	 */
    protected function parseWaypoints($xmlObject) {
		if (!in_array('wpt', $this->gpxTypes->get('gpxType'))) {
			return [];
		}
        $points = [];
        $wpt_elements = $xmlObject->getElementsByTagName('wpt');
        foreach ($wpt_elements as $wpt) {
			$point = $this->parsePoint($wpt);
			$point->setData('gpxType', 'waypoint');
            $points[] = $point;
        }
        return $points;
    }

	/**
	 * @param DOMDocument $xmlObject
	 * @return LineString[]
	 */
    protected function parseTracks($xmlObject) {
		if (!in_array('trk', $this->gpxTypes->get('gpxType'))) {
			return [];
		}
        $tracks = [];
        $trk_elements = $xmlObject->getElementsByTagName('trk');
        foreach ($trk_elements as $trk) {
            $segments = [];
            /** @noinspection SpellCheckingInspection */
            foreach ($this->childElements($trk, 'trkseg') as $trkseg) {
                $points = [];
                /** @noinspection SpellCheckingInspection */
                foreach ($this->childElements($trkseg, 'trkpt') as $trkpt) {
                    /** @noinspection SpellCheckingInspection */
                    $points[] = $this->parsePoint($trkpt);
                }
                $segments[] = new LineString($points);
            }
			$track = count($segments) === 1 ? $segments[0] : new MultiLineString($segments);
			$track->setData($this->parseNodeProperties($trk, $this->gpxTypes->get('trkType')));
			$track->setData('gpxType', 'track');
			$tracks[] = $track;
        }
        return $tracks;
    }

	/**
	 * @param DOMDocument $xmlObject
	 * @return LineString[]
	 */
    protected function parseRoutes($xmlObject) {
		if (!in_array('rte', $this->gpxTypes->get('gpxType'))) {
			return [];
		}
        $lines = [];
        $rte_elements = $xmlObject->getElementsByTagName('rte');
        foreach ($rte_elements as $rte) {
            $components = [];
            /** @noinspection SpellCheckingInspection */
            foreach ($this->childElements($rte, 'rtept') as $routePoint) {
				/** @noinspection SpellCheckingInspection */
				$components[] = $this->parsePoint($routePoint);
            }
			$line = new LineString($components);
			$line->setData($this->parseNodeProperties($rte, $this->gpxTypes->get('rteType')));
			$line->setData('gpxType', 'route');
			$lines[] = $line;
        }
        return $lines;
    }

	/**
	 * Parses a DOMNode and returns its content in a multidimensional associative array
	 * eg: <wpt><name>Test</name><link href="example.com"><text>Example</text></link></wpt>
	 * to: ['name' => 'Test', 'link' => ['text'] => 'Example', '@attributes' => ['href' => 'example.com']]
	 *
	 * @param \DOMNode $node
	 * @param string[]|null $tagList
	 * @return array|string
	 */
	protected static function parseNodeProperties($node, $tagList = null) {
		if ($node->nodeType === XML_TEXT_NODE) {
			return $node->nodeValue;
		}
		$result = [];
		foreach($node->childNodes as $childNode) {
			/** @var \DOMNode $childNode */
			if ($childNode->hasChildNodes()) {
				if ($tagList === null || in_array($childNode->nodeName, $tagList ?: [])) {
					if ($node->firstChild->nodeName == $node->lastChild->nodeName && $node->childNodes->length > 1) {
						$result[$childNode->nodeName][] = self::parseNodeProperties($childNode);
					} else {
						$result[$childNode->nodeName] = self::parseNodeProperties($childNode);
					}
				}
			} else if ($childNode->nodeType === 1 && in_array($childNode->nodeName, $tagList ?: [])) {
				$result[$childNode->nodeName] = self::parseNodeProperties($childNode);
			} else if ($childNode->nodeType === 3) {
				$result = $childNode->nodeValue;
			}
		}
		if ($node->hasAttributes()) {
			if (is_string($result)) {
				// As of the GPX specification text node cannot have attributes, thus this never happens
				$result = ['#text' => $result];
			}
			$attributes = [];
			foreach ($node->attributes as $attribute) {
				if ($attribute->name !== 'lat' && $attribute->name !== 'lon' && trim($attribute->value) !== '') {
					$attributes[$attribute->name] = trim($attribute->value);
				}
			}
			if (count($attributes)) {
				$result['@attributes'] = $attributes;
			}
		}
		return $result;
	}


    /**
     * Serialize geometries into a GPX string.
     *
     * @param Geometry|GeometryCollection $geometry
     * @param string|null $namespace
	 * @param array|null $allowedElements Which elements can be added to each GPX type
	 *                   If not specified, every element defined in the GPX specification can be added
	 *                   Can be overwritten with an associative array, with type name in keys.
	 *                   eg.: ['wptType' => ['ele', 'name'], 'trkptType' => ['ele'], 'metadataType' => null]
     * @return string The GPX string representation of the input geometries
     */
    public function write(Geometry $geometry, $namespace = null, $allowedElements = null) {
        if ($namespace) {
            $this->nss = $namespace . ':';
        }
		$this->gpxTypes = new GpxTypes($allowedElements);

        return
'<?xml version="1.0" encoding="UTF-8"?>
<' . $this->nss . 'gpx creator="geoPHP" version="1.1"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns="http://www.topografix.com/GPX/1/1"
  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" >

' . $this->geometryToGPX($geometry) .
'</' . $this->nss . 'gpx>
';
    }

    /**
     * @param Geometry|Collection $geometry
     * @return string
     */
    protected function geometryToGPX($geometry) {
        switch ($geometry->geometryType()) {
            case Geometry::POINT:
				/** @var Point $geometry */
                return $this->pointToGPX($geometry);
            case Geometry::LINE_STRING:
            case Geometry::MULTI_LINE_STRING:
                /** @var LineString $geometry */
                return $this->linestringToGPX($geometry);
            case Geometry::POLYGON:
            case Geometry::MULTI_POINT:
            case Geometry::MULTI_POLYGON:
            case Geometry::GEOMETRY_COLLECTION:
                return $this->collectionToGPX($geometry);
        }
        return '';
    }

    /**
     * @param Point $geom
     * @param string $tag Can be "wpt", "trkpt" or "rtept"
     * @return string
     */
    private function pointToGPX($geom, $tag = 'wpt') {
		if ($geom->isEmpty() || ($tag === 'wpt' && !in_array($tag, $this->gpxTypes->get('gpxType')))) {
			return '';
		}
        $indent = $tag === 'trkpt' ? "\t\t" : ($tag === 'rtept' ? "\t" : '');

        if ( $geom->hasZ() || $geom->getData() !== null ) {
            $node = $indent . "<".$this->nss . $tag ." lat=\"".$geom->getY()."\" lon=\"".$geom->getX()."\">\n";
            if ($geom->hasZ()) {
                $geom->setData('ele', $geom->z());
            }
			$node .= self::processGeometryData($geom, $this->gpxTypes->get($tag . 'Type'), $indent."\t") .
					$indent . "</".$this->nss. $tag . ">\n";
			if ($geom->hasZ()) {
				$geom->setData('ele', null);
			}
            return $node;
        }
        return $indent . "<".$this->nss. $tag . " lat=\"".$geom->getY()."\" lon=\"".$geom->getX()."\" />\n";
    }

    /**
     * Writes a LineString or MultiLineString to the GPX
     *
     * The (Multi)LineString will be included in a <trk></trk> block
     * The LineString or each LineString of the MultiLineString will be in <trkseg> </trkseg> inside the <trk>
     *
     * @param LineString|MultiLineString $geom
     * @return string
     */
    private function linestringToGPX($geom) {
		$isTrack = $geom->getData('gpxType') === 'route' ? false : true;
		if ($geom->isEmpty() || !in_array($isTrack ? 'trk' : 'rte', $this->gpxTypes->get('gpxType'))) {
			return '';
		}

		if ($isTrack) {	// write as <trk>

			/** @noinspection SpellCheckingInspection */
			$gpx = "<" . $this->nss . "trk>\n" . self::processGeometryData($geom, $this->gpxTypes->get('trkType'));
			$components = $geom->geometryType() === 'LineString' ? [$geom] : $geom->getComponents();
			foreach ($components as $lineString) {
				$gpx .= "\t<" . $this->nss . "trkseg>\n";
				foreach ($lineString->getPoints() as $point) {
					$gpx .= $this->pointToGPX($point, 'trkpt');
				}
				$gpx .= "\t</" . $this->nss . "trkseg>\n";
			}
			/** @noinspection SpellCheckingInspection */
			$gpx .= "</" . $this->nss . "trk>\n";

		} else {	// write as <rte>

			/** @noinspection SpellCheckingInspection */
			$gpx = "<" . $this->nss . "rte>\n" . self::processGeometryData($geom, $this->gpxTypes->get('rteType'));
			foreach ($geom->getPoints() as $point) {
				$gpx .= $this->pointToGPX($point, 'rtept');
			}
			/** @noinspection SpellCheckingInspection */
			$gpx .= "</" . $this->nss . "rte>\n";
		}

        return $gpx;
    }

    /**
     * @param Collection $geometry
     * @return string
     */
    public function collectionToGPX($geometry) {
		$metadata = self::processGeometryData($geometry, $this->gpxTypes->get('metadataType'));
		$metadata = empty($metadata) || !in_array('metadataType', $this->gpxTypes->get('gpxType'))
				? ''
				: "<metadata>\n{$metadata}</metadata>\n\n";
		$wayPoints = $routes = $tracks = "";

		foreach ($geometry->getComponents() as $component) {
			if (strpos($component->geometryType(), 'Point') !== false) {
				$wayPoints .= $this->geometryToGPX($component);
			}
			if (strpos($component->geometryType(), 'LineString') !== false && $component->getData('gpxType') === 'route') {
				$routes .= $this->geometryToGPX($component);
			}
			if (strpos($component->geometryType(), 'LineString') !== false && $component->getData('gpxType') !== 'route') {
				$tracks .= $this->geometryToGPX($component);
			}
			if (strpos($component->geometryType(), 'Point') === false && strpos($component->geometryType(), 'LineString') === false) {
				return $this->geometryToGPX($component);
			}
		}

        return $metadata . $wayPoints . $routes . $tracks;
    }

	/**
	 * @param Geometry $geometry
	 * @param string[] $tagList Allowed tags
	 * @param string $indent
	 * @return string
	 */
	protected static function processGeometryData($geometry, $tagList, $indent = "\t") {
		$tags = '';
		if ($geometry->getData() !== null) {
			foreach ($tagList as $tagName) {
				if ($geometry->hasDataProperty($tagName)) {
					$tags .= self::createNodes($tagName, $geometry->getData($tagName), $indent) . "\n";
				}
			}
		}
		return $tags;
	}

	/**
	 * @param string $tagName
	 * @param string|array $value
	 * @param string $indent
	 * @return string
	 */
	protected static function createNodes($tagName, $value, $indent) {
		$attributes = '';
		if (!is_array($value)) {
			$returnValue = $value;
		} else {
			$returnValue = '';
			if (array_key_exists('@attributes', $value)) {
				$attributes = '';
				foreach($value['@attributes'] as $attributeName => $attributeValue) {
					$attributes .= ' ' . $attributeName . '="' . $attributeValue . '"';
				}
				unset($value['@attributes']);
			}
			foreach ($value as $subKey => $subValue) {
				$returnValue .= "\n" . self::createNodes($subKey, $subValue, $indent."\t") . "\n" . $indent;
			}
		}
		return $indent . "<{$tagName}{$attributes}>{$returnValue}</{$tagName}>";
	}

}

/**
 * Class GpxTypes
 * Defines the available GPX types and their allowed elements following the GPX specification
 *
 * @see http://www.topografix.com/gpx/1/1/
 * @package geoPHP\Adapter
 */
class GpxTypes {

	// TODO: convert these static properties to constants once HHVM fixes this bug: https://github.com/facebook/hhvm/issues/4277
	/**
	 * @var array Allowed elements in <gpx>
	 * @see http://www.topografix.com/gpx/1/1/#type_gpxType
	 */
	public static $gpxTypeElements = [
			'metadata', 'wpt', 'rte', 'trk'
	];
	/**
	 * @var array Allowed elements in <trk>
	 * @see http://www.topografix.com/gpx/1/1/#type_trkType
	 */
	public static $trkTypeElements = [
			'name', 'cmt', 'desc', 'src', 'link', 'number', 'type'
	];
	/**
	 * @var array Allowed elements in <rte>
	 * @see http://www.topografix.com/gpx/1/1/#type_rteType
	 */
	public static $rteTypeElements = ['name', 'cmt', 'desc', 'src', 'link', 'number', 'type'];	// same as trkTypeElements
	/**
	 * @var array Allowed elements in <wpt>
	 * @see http://www.topografix.com/gpx/1/1/#type_wptType
	 */
	public static $wptTypeElements = [
			'ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt', 'desc', 'src', 'link', 'sym', 'type',
			'fix', 'sat', 'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid'
	];
	/**
	 * @var array Same as wptType
	 */
	public static $trkptTypeElements = [	// same as wptTypeElements
			'ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt', 'desc', 'src', 'link', 'sym', 'type',
			'fix', 'sat', 'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid'
	];
	/**
	 * @var array Same as wptType
	 */
	public static $rteptTypeElements = [	// same as wptTypeElements
			'ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt', 'desc', 'src', 'link', 'sym', 'type',
			'fix', 'sat', 'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid'
	];
	/**
	 * @var array Allowed elements in <metadata>
	 * @see http://www.topografix.com/gpx/1/1/#type_metadataType
	 */
	public static $metadataTypeElements = [
			'name', 'desc', 'author', 'copyright', 'link', 'time', 'keywords', 'bounds'
	];

	protected $allowedGpxTypeElements;
	protected $allowedTrkTypeElements;
	protected $allowedRteTypeElements;
	protected $allowedWptTypeElements;
	protected $allowedTrkptTypeElements;
	protected $allowedRteptTypeElements;
	protected $allowedMetadataTypeElements;

	/**
	 * GpxTypes constructor.
	 *
	 * @param array|null $allowedElements Which elements can be used in each GPX type
	 *                   If not specified, every element defined in the GPX specification can be used
	 *                   Can be overwritten with an associative array, with type name in keys.
	 *                   eg.: ['wptType' => ['ele', 'name'], 'trkptType' => ['ele'], 'metadataType' => null]
	 */
	function __construct($allowedElements = null) {
		$this->allowedGpxTypeElements = self::$gpxTypeElements;
		$this->allowedTrkTypeElements = self::$trkTypeElements;
		$this->allowedRteTypeElements = self::$rteTypeElements;
		$this->allowedWptTypeElements = self::$wptTypeElements;
		$this->allowedTrkptTypeElements = self::$trkTypeElements;
		$this->allowedRteptTypeElements = self::$rteptTypeElements;
		$this->allowedMetadataTypeElements = self::$metadataTypeElements;

		if (is_array($allowedElements)) {
			foreach ($allowedElements as $type => $elements) {
				$elements = is_array($elements) ? $elements : [$elements];
				$this->{'allowed' . ucfirst($type) . 'Elements'} = [];
				foreach ($this::${$type.'Elements'} as $availableType) {
					if (in_array($availableType, $elements)) {
						$this->{'allowed' . ucfirst($type) . 'Elements'}[] = $availableType;
					}
				}
			}
		}
	}

	/**
	 * Returns an array of allowed elements for the given GPX type
	 * eg. "gpxType" returns ['metadata', 'wpt', 'rte', 'trk']
	 *
	 * @param string $type One of the following GPX types: gpxType, trkType, rteType, wptType, trkptType, rteptType, metadataType
	 * @return string[]
	 */
	public function get($type) {
		$propertyName = 'allowed' . ucfirst($type) . 'Elements';
		if (isset($this->{$propertyName})) {
			return $this->{$propertyName};
		}
		return [];
	}
}
