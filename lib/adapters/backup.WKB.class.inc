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
 */
class WKB extends GeoAdapter
{
  private $mem;
  private $z = FALSE;
  private $m = FALSE;
  private $dimension = 2;
  private $maptype = array(
    'Point'              => 1,
    'LineString'         => 2,
    'Polygon'            => 3,
    'MultiPoint'         => 4,
    'MultiLineString'    => 5,
    'MultiPolygon'       => 6,
    'GeometryCollection' => 7,
    'CircularString'     => 8,
    'CompoundCurve'      => 9,
    'CurvePolygon'       => 10,
    'MultiCurve'         => 11,
    'MultiSurface'       => 12,
    'Curve'              => 13,
    'Surface'            => 14,
    'PolyhedralSurface'  => 15,
    'TIN'                => 16,
    'Triangle'           => 17,
  );


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
    if ($is_hex_string) {
      $wkb = pack('H*',$wkb);
    }

    if (empty($wkb)) {
      throw new Exception('Cannot read empty WKB geometry. Found ' . gettype($wkb));
    }

    $this->mem = fopen('php://memory', 'r+');
    fwrite($this->mem, $wkb);
    fseek($this->mem, 0);

    $geometry = $this->getGeometry();
    fclose($this->mem);
    return $geometry;
  }

  function getGeometry() {
    $base_info = unpack("corder/ctype/cz/cm/cs", fread($this->mem, 5));
    if ($base_info['order'] !== 1) {
      throw new Exception('Only NDR (little endian) SKB format is supported at the moment');
    }

    if ($base_info['z']) {
      $this->dimension++;
      $this->z = TRUE;
    }
    if ($base_info['m']) {
      $this->dimension++;
      $this->m = TRUE;
    }

    // If there is SRID information, ignore it - use EWKB Adapter to get SRID support
    if ($base_info['s']) {
      fread($this->mem, 4);
    }

    switch ($base_info['type']) {
      case 1:
        return $this->getPoint();
      case 2:
        return $this->getLinstring();
      case 3:
        return $this->getPolygon();
      case 4:
        return $this->getMulti('point');
      case 5:
        return $this->getMulti('line');
      case 6:
        return $this->getMulti('polygon');
      case 7:
        return $this->getMulti('geometry');
    }
  }

  function getPoint() {
    $point_coords = unpack("d*", fread($this->mem,$this->dimension*8));
    
    if (!$this->m && !$this->z) {
      return new Point($point_coords[1],$point_coords[2]);
    }
    else if ($this-m && $this-m) {
      return new Point($point_coords[1],$point_coords[2], $point_coords[3], $point_coords[4]);
    }
    else if ($this->z) {
      return new Point($point_coords[1],$point_coords[2], $point_coords[3]);
    }
    else if ($this->m) {
      return new Point($point_coords[1],$point_coords[2], NULL, $point_coords[3]);
    }
  }

  function getLinstring() {
    // Get the number of points expected in this string out of the first 4 bytes
    $line_length = unpack('L',fread($this->mem,4));

    // Return an empty linestring if there is no line-length
    if (!$line_length[1]) return new LineString();
    
    $i = 1;
    while ($i <= $line_length[1]) {
      $components[] = $this->getPoint();
      $i++;
    }
    
    return new LineString($components);
  }

  function getPolygon() {
    // Get the number of linestring expected in this poly out of the first 4 bytes
    $poly_length = unpack('L',fread($this->mem,4));

    $components = array();
    $i = 1;
    while ($i <= $poly_length[1]) {
      $components[] = $this->getLinstring();
      $i++;
    }
    return new Polygon($components);
  }

  function getMulti($type) {
    // Get the number of items expected in this multi out of the first 4 bytes
    $multi_length = unpack('L',fread($this->mem,4));

    $components = array();
    $i = 1;
    while ($i <= $multi_length[1]) {
      $components[] = $this->getGeometry();
      $i++;
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
  public function write(Geometry $geometry, $write_as_hex = FALSE) {
    // Write z and m info. Note that this may change when parsing Multi-types as we decend into their components
    //$this->z = $geometry->hasZ();
    //$this->m = $geometry->isMeasured();

    // We always write into NDR (little endian)
    $wkb  = pack('c', 1);
    $wkb .= pack('C', (int) $this->maptype[$geometry->getGeomType()]);
    $wkb .= pack('C', (int) $this->z);
    $wkb .= pack('C', (int) $this->m);
    
    // Set SRID to 0
    $wkb .= pack('c', 0);
    
    switch ($geometry->getGeomType()) {
      case 'Point';
        $wkb .= $this->writePoint($geometry);
        break;
      case 'LineString';
        $wkb .= $this->writeLineString($geometry);
        break;
      case 'Polygon';
        $wkb .= $this->writePolygon($geometry);
        break;
      case 'MultiPoint';
      case 'MultiLineString';
      case 'MultiPolygon';
      case 'GeometryCollection';
        $wkb .= $this->writeMulti($geometry);
        break;
    }

    if ($write_as_hex) {
      $unpacked = unpack('H*', $wkb);
      return $unpacked[1];
    }
    else {
      return $wkb;
    }
  }

  function writePoint($point) {
    
    // Set the coords
    if ($this->z && $this->m) {
      return pack('dddd', $point->x(), $point->y(), $point->z(), $point->m());
    }
    else if ($this->z) {
      return pack('ddd', $point->x(), $point->y(), $point->z());
    }
    else if ($this->m) {
      return pack('ddd', $point->x(), $point->y(), $point->m());
    }
    else {
      return pack('dd', $point->x(), $point->y());
    }
  }

  function writeLineString($line) {
    // Set the number of points in this line
    $wkb = pack('L',$line->numPoints());

    // Set the coords
    foreach ($line->getComponents() as $point) {
      $wkb .= $this->writePoint($point);
    }

    return $wkb;
  }

  function writePolygon($poly) {
    // Set the number of lines in this poly
    $wkb = pack('L',$poly->numGeometries());

    // Write the lines
    foreach ($poly->getComponents() as $line) {
      $wkb .= $this->writeLineString($line);
    }

    return $wkb;
  }

  function writeMulti($geometry) {
    // Set the number of components
    $wkb = pack('L',$geometry->numGeometries());

    // Write the components
    foreach ($geometry->getComponents() as $component) {
      $wkb .= $this->write($component);
    }

    return $wkb;
  }

}
