<?php
/*
 * (c) Patrick Hayes
*
* This code is open-source and licenced under the Modified BSD License.
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * PHP Geometry/WKB encoder/decoder
 *
 * Mickael Desgranges mickael.desgranges@bcbgeo.com
 * wkb spec can be found here:
 * http://www.opengeospatial.org/standards/sfa
 * Simple Feature Access  1.2.1
 * some test case here: http://svn.osgeo.org/geos/trunk/tests/unit/capi/GEOSGeomFromWKBTest.cpp
 */
class WKB extends GeoAdapter {
	private $dimension = 2;	
	protected $hasZ      = false;
	protected $measured  = false;

	const NDR = 1;
	const XDR = 0;

	private $wkb;
	private $unpackerPosition 	= 0;
	private $packerPosition   	= 0;
	private $packerWkb			= '';

	private $uint_marker;
	private $double_marker;

	/**
	 * Read WKB into geometry objects
	 *
	 * @param string $wkb
	 *   Well-known-binary string
	 * @param bool $is_hex_string
	 *   If this is a hexedecimal string that is in need of packing
	 *
	 * @return Geometry
	 */
	public function read($wkb, $is_hex_string = FALSE) {		
		if ($is_hex_string) $wkb = pack('H*',$wkb);
		if (empty($wkb)) throw new Exception('Cannot read empty WKB geometry. Found ' . gettype($wkb));
		$this->dimension = 2;
		$this->hasZ      = false;
		$this->measured  = false;
		$this->unpackerPosition = 0;
		$this->wkb = $wkb;
		
		$this->set_endianness($this->read_byte());
		return $this->getGeometry();
	}

	protected function read_double() {						
		$packed_double = substr($this->wkb, $this->unpackerPosition, 8);
		$this->unpackerPosition += 8;
		if (!$packed_double || strlen($packed_double) < 8 ) throw new Exception("Truncated data");
		return current(unpack($this->double_marker, $packed_double));
	}

	protected function read_uint() {			
		$packed_uint = substr($this->wkb, $this->unpackerPosition, 4);
		$this->unpackerPosition += 4;
		if (!$packed_uint || strlen($packed_uint) < 4) throw new Exception("Truncated data");
		return current(unpack($this->uint_marker, $packed_uint));
	}

	protected function read_byte() {
		$packed_byte = substr($this->wkb, $this->unpackerPosition, 1);
		$this->unpackerPosition += 1;
		if ($packed_byte === null || strlen($packed_byte) < 1) throw new Exception("Truncated data");
		return current(unpack("C", $packed_byte));
	}

	protected function write_double($double) {
		$this->packerPosition += 8;
		$this->packerWkb .= pack($this->double_marker, (float) $double);
		return $this;
	}

	protected function write_uint($uint) {
		$this->packerPosition += 4;
		$this->packerWkb .= pack($this->uint_marker, (int) $uint);
		return $this;
	}

	protected function write_byte($byte) {
		$this->packerPosition += 1;
		$this->packerWkb .= pack("C", $byte);
		return $this;
	}

	protected function set_endianness($eness) {
		if ($eness == self::NDR) {
			$this->uint_marker = 'V';
			$this->double_marker = 'd'; // should be E
		}
		elseif ($eness == self::XDR) {
			$this->uint_marker = 'N';
			$this->double_marker = 'd'; // should be G
		}
	}

	protected function getGeometry() {
		$base_info =  $this->read_uint();
		$this->measured = $this->hasZ = false; // reset for mixed geometry collection
		if ( $base_info > 3000 ) {
			$this->dimension += 2;
			$this->measured = $this->hasZ = TRUE;
			$type = $base_info - 3000;
		}
		else if ( $base_info > 2000 ) {
			$this->dimension++;
			$this->measured = TRUE;
			$type = $base_info - 2000;
		}
		else if ( $base_info > 1000 ) {
			$this->dimension++;
			$this->hasZ = TRUE;
			$type = $base_info - 1000;
		}
		else {
			$type = $base_info;
		}
		
		switch ($type) {
			case 1:
				$geom = $this->getPoint();
				break;
			case 2:
				$geom = $this->getLineString();
				break;
			case 3:
				$geom = $this->getPolygon();
				break;
			case 4:
				$geom = $this->getMulti('point');
				break;
			case 5:
				$geom = $this->getMulti('line');
				break;
			case 6:
				$geom = $this->getMulti('polygon');
				break;
			case 7:
				$geom = $this->getMulti('geometry');
				break;
			default:
				throw new Exception('geometry type unknow: '.$type);
		}
		$geom->set3d($this->hasZ);
		$geom->setMeasured($this->measured);
		return $geom;
	}

	protected function getPoint() {
		$z = $m = null;
		$x = $this->read_double();
		$y = $this->read_double();
		if ( $this->hasZ ) 		$z= $this->read_double();
		if ( $this->measured )  $m= $this->read_double();		
		return new Point($x, $y, $z, $m);
	}

	protected function getLineString() {
		// Get the number of points expected in this string out of the first 4 bytes
		$line_length = $this->read_uint();

		// Return an empty linestring if there is no line-length
		if (!$line_length) return new LineString();
		
		// We have our coords, build up the linestring
		$components = array();
		for ($i=0; $i<$line_length; $i++) {
			$components[] = $this->getPoint();
		}
		return new LineString($components);
	}

	protected function getPolygon() {
		// Get the number of linestring expected in this poly out of the first 4 bytes
		$poly_length = $this->read_uint();
		
		$components = array();
		for ($i=0; $i<$poly_length; $i++) {
			$components[] = $this->getLineString();
		}
		return new Polygon($components);
	}

	protected function getMulti($type) {
		// Get the number of items expected in this multi out of the first 4 bytes
		$multi_length = $this->read_uint();
		$components = array();
		for ($i=0; $i<$multi_length; $i++) {			
			switch ($type) {
				case 'point':
					$components[] = $this->getPoint();
					break;
				case 'line':
					$components[] = $this->getLineString();
					break;
				case 'polygon':
					$components[] = $this->getPolygon();
					break;
				case 'geometry':
					$components[] = $this->getGeometry();
					break;
			}
		}		
		
		switch ($type) {
			case 'point':
				return new MultiPoint($components);
			case 'line':
				return new MultiLineString($components);
			case 'polygon':
				return new MultiPolygon($components);
			case 'geometry':
				return new GeometryCollection($components);
		}
		
	}

	
	
	/**
	 * Serialize geometries into WKB string.
	 *
	 * @param Geometry $geometry
	 *
	 * @return string The WKB string representation of the input geometries
	 */
	public function write(Geometry $geometry, $write_as_hex = FALSE, $endianess=1) {
		$this->packerPosition = 0;
		$this->packerWkb = '';
		
		// We always write into NDR (little endian) by default
		$this->set_endianness($endianess);
		$this->write_byte($endianess);		
		$this->writeType($geometry);

		if ($write_as_hex) {
			return  current(unpack('H*', $this->packerWkb));
		}
		return $this->packerWkb;
	}
	
	protected function writeType($geometry) {
		//+ 1000 Z
		//+ 2000 M
		//+ 3000 ZM
		$type = 0;
		if ( $geometry->isMeasured() && $geometry->hasZ() ) {
			$type = 3000;
		}
		else if ( $geometry->isMeasured() ) {
			$type = 2000;
		}
		else if ( $geometry->hasZ() ) {
			$type = 1000;
		}
		 
		switch ($geometry->getGeomType()) {
			case 'Point';
				$type += 1;
				$this->write_uint($type);
				$this->writePoint($geometry);
				break;
			case 'LineString';
				$type += 2;
				$this->write_uint($type);
				$this->writeLineString($geometry);
				break;
			case 'Polygon';
				$type += 3;
				$this->write_uint($type);
				$this->writePolygon($geometry);
				break;
			case 'MultiPoint';
				$type += 4;
				$this->write_uint($type);
				$this->writeMulti('point', $geometry);
				break;
			case 'MultiLineString';
				$type += 5;
				$this->write_uint($type);
				$this->writeMulti('line', $geometry);
				break;
			case 'MultiPolygon';
				$type += 6;
				$this->write_uint($type);
				$this->writeMulti('polygon', $geometry);
				break;
			case 'GeometryCollection';
				$type += 7;
				$this->write_uint($type);
				$this->writeMulti('geometry', $geometry);
				break;
		}
	}

	protected function writePoint($point) {		
		$this->write_double($point->x());
		$this->write_double($point->y());
		if( $point->hasZ() ) {
			$this->write_double($point->z());
		}
		if ($point->isMeasured() ) {
			$this->write_double($point->m());
		}		
	}

	protected function writeLineString($line) {
		// Set the number of points in this line
		$this->write_uint($line->numPoints());
		// Set the coords
		foreach ($line->getComponents() as $point) {
			$this->writePoint($point);
		}
	}

	protected function writePolygon($poly) {
		// Set the number of lines in this poly
		$this->write_uint($poly->numGeometries());
		// Write the lines
		foreach ($poly->getComponents() as $line) {
			$this->writeLineString($line);
		}
	}

	protected function writeMulti($type, $geometry) {
		// Set the number of components
		$this->write_uint($geometry->numGeometries());
		// Write the components
		foreach ($geometry->getComponents() as $component) {
			switch ($type) {
				case 'point':
					$this->writePoint($component);
					break;
				case 'line':
					$this->writeLineString($component);
					break;
				case 'polygon':
					$this->writePolygon($component);
					break;
				case 'geometry':
					$this->writeType($component);
			}			
		}
	}
}