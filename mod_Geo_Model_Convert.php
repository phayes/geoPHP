<?php 
// handy wrapper for geoPhp
require_once 'geoPHP/geoPHP.inc';
class mod_Geo_Model_Convert {	
	/**
	 * @var Geometry
	 */
	public $geometry;

	public function gpx($gpx) { 
		$this->geometry = geoPHP::load($gpx, 'gpx');
		return $this;
	}
	
	public function toGpx() { 
		return $this->geometry->out('gpx');
	}

	
	public function wkt($wkt) {
		$this->geometry = geoPHP::load($wkt, 'wkt');
		return $this;
	}
	
	public function toWkt() {
		return $this->geometry->out('wkt');
	}
	
	
	public function ewkt($ewkt) {
		$this->geometry = geoPHP::load($ewkt, 'ewkt');
		return $this;
	}
	
	public function toEwkt() {
		return $this->geometry->out('ewkt');
	}
	
	
	public function wkb($wkb) {
		$this->geometry = geoPHP::load($wkb, 'wkb');
		return $this;
	}
	
	public function toWkb() {
		return $this->geometry->out('wkb');
	}
	
	
	public function ewkb($ewkb) {
		$this->geometry = geoPHP::load($ewkb, 'ewkb');
		return $this;
	}
	
	public function toEwkb() {
		return $this->geometry->out('ewkb');
	}
	
	
	public function geoJson($geoJson) {
		$this->geometry = geoPHP::load($geoJson, 'json');
		return $this;
	}
	
	public function toGeoJson() {
		return $this->geometry->out('json');
	}

	
	public function kml($kml) {
		$this->geometry = geoPHP::load($kml, 'kml');
		return $this;
	}
	
	public function toKml() {
		return $this->geometry->out('kml');
	}

	
	public function geoRss($georss) {
		$this->geometry = geoPHP::load($georss, 'georss');
		return $this;
	}
	
	public function toGeoRss() {
		return $this->geometry->out('georss');
	}
	
	
	public function googleGeocode($GoogleGeocode) {
		$this->geometry = geoPHP::load($GoogleGeocode, 'google_geocode');
		return $this;
	}
	
	public function toGoogleGeocode() {
		return $this->geometry->out('google_geocode');
	}

	
	public function geoHash($geohash) {
		$this->geometry = geoPHP::load($geohash, 'geohash');
		return $this;
	}
	
	public function toGeoHash() {
		return $this->geometry->out('geohash');
	}		
}