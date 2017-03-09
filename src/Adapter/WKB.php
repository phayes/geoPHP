<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiLineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\MultiPolygon;

/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/WKB encoder/decoder
 * Reader can decode EWKB too. Writer always encodes valid WKBs
 *
 */
class WKB implements GeoAdapter
{
    const Z_MASK = 0x80000000;
    const M_MASK = 0x40000000;
    const SRID_MASK = 0x20000000;
    const WKB_XDR = 1;
    const WKB_NDR = 0;

    protected $hasZ = false;
    protected $hasM = false;
    protected $hasSRID = false;
    protected $SRID = null;
    protected $dimension = 2;
    /** @var  BinaryReader $reader */
    protected $reader;
    /** @var  BinaryWriter $writer */
    protected $writer;
    public static $mapType = array(
            'Geometry'           => 0,
            'Point'              => 1,
            'LineString'         => 2,
            'Polygon'            => 3,
            'MultiPoint'         => 4,
            'MultiLineString'    => 5,
            'MultiPolygon'       => 6,
            'GeometryCollection' => 7,
            //Not supported types:
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
     * @param string $wkb Well-known-binary string
     * @param bool $is_hex_string If this is a hexadecimal string that is in need of packing
     *
     * @return Geometry
     *
     * @throws \Exception
     */
    public function read($wkb, $is_hex_string = FALSE) {
        if ($is_hex_string) {
            $wkb = pack('H*',$wkb);
        }

        if (empty($wkb)) {
            throw new \Exception('Cannot read empty WKB geometry. Found ' . gettype($wkb));
        }

        $this->reader = new BinaryReader($wkb);

        $geometry = $this->getGeometry();

        $this->reader->close();

        return $geometry;
    }

    /**
     * @return Geometry
     * @throws \Exception
     */
    function getGeometry() {
        $this->hasZ = false;
        $this->hasM = false;
        $SRID = null;

        $this->reader->setEndianness( $this->reader->readSInt8() === self::WKB_XDR ? BinaryReader::LITTLE_ENDIAN : BinaryReader::BIG_ENDIAN);

        $wkbType = $this->reader->readUInt32();

        if (($wkbType & $this::SRID_MASK) === $this::SRID_MASK) {
            $SRID = $this->reader->readUInt32();
        }
        $geometryType = null;
        if ($wkbType >= 1000 && $wkbType < 2000) {
            $this->hasZ = true;
            $geometryType = $wkbType - 1000;
        }
        else if ($wkbType >= 2000 && $wkbType < 3000) {
            $this->hasM = true;
            $geometryType = $wkbType - 2000;
        }
        else if ($wkbType >= 3000 && $wkbType < 4000) {
            $this->hasZ = true;
            $this->hasM = true;
            $geometryType = $wkbType - 3000;
        }

        if ($wkbType & $this::Z_MASK) {
            $this->hasZ = true;
        }
        if ($wkbType & $this::M_MASK) {
            $this->hasM = true;
        }
        $this->dimension = 2 + ($this->hasZ ? 1 : 0) + ($this->hasM ? 1 : 0);

        if (!$geometryType) {
            $geometryType = $wkbType & 0xF; // remove any masks from type
        }
        $geometry = null;
        switch ($geometryType) {
            case 1:
                $geometry = $this->getPoint();
                break;
            case 2:
                $geometry = $this->getLineString();
                break;
            case 3:
                $geometry = $this->getPolygon();
                break;
            case 4:
                $geometry = $this->getMulti('point');
                break;
            case 5:
                $geometry = $this->getMulti('line');
                break;
            case 6:
                $geometry = $this->getMulti('polygon');
                break;
            case 7:
                $geometry = $this->getMulti('geometry');
                break;
            default:
                throw new \Exception('Geometry type ' . $geometryType .
                        ' (' . (array_search($geometryType, self::$mapType) ?: 'unknown') . ') not supported');
        }
        if ($geometry && $SRID) {
            $geometry->setSRID($SRID);
        }
        return $geometry;
    }

    function getPoint() {
        $coordinates = $this->reader->readDoubles($this->dimension * 8);
        $point = null;
        switch (count($coordinates)) {
            case 2:
                $point = new Point($coordinates[0], $coordinates[1]);
                break;
            case 3:
                if ($this->hasZ) {
                    $point = new Point($coordinates[0], $coordinates[1], $coordinates[2]);
                } else {
                    $point = new Point($coordinates[0], $coordinates[1], null, $coordinates[2]);
                }
                break;
            case 4:
                $point = new Point($coordinates[0], $coordinates[1], $coordinates[2], $coordinates[3]);
                break;
        }
        return $point;
    }

    function getLineString() {
        // Get the number of points expected in this string out of the first 4 bytes
        $line_length = $this->reader->readUInt32();

        // Return an empty linestring if there is no line-length
        if (!$line_length) {
            return new LineString();
        }

        $components = array();
        for($i=0; $i < $line_length; ++$i) {
            $point = $this->getPoint();
            if ($point) {
                $components[] = $point;
            }
        }
        return new LineString($components);
    }

    function getPolygon() {
        // Get the number of linestring expected in this poly out of the first 4 bytes
        $poly_length = $this->reader->readUInt32();

        $components = array();
        $i = 1;
        while ($i <= $poly_length) {
            $ring = $this->getLineString();
            if (!$ring->isEmpty()) {
                $components[] = $ring;
            }
            $i++;
        }

        return new Polygon($components);
    }

    function getMulti($type) {
        // Get the number of items expected in this multi out of the first 4 bytes
        $multi_length = $this->reader->readUInt32();

        $components = array();
        for ($i=0; $i < $multi_length; $i++) {
            $component = $this->getGeometry();
            $component->setSRID(null);
            $components[] = $component;
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
        return null;
    }

    /**
     * Serialize geometries into WKB string.
     *
     * @param Geometry $geometry The geometry
     * @param boolean $writeAsHex Write the result in binary or hexadecimal system
     * @param boolean $bigEndian Write in BigEndian or LittleEndian byte order
     *
     * @return string The WKB string representation of the input geometries
     */
    public function write(Geometry $geometry, $writeAsHex = false, $bigEndian = false) {

        $this->writer = new BinaryWriter($bigEndian ? BinaryWriter::BIG_ENDIAN : BinaryWriter::LITTLE_ENDIAN);

        $wkb = $this->writeGeometry($geometry);

        return $writeAsHex ? current(unpack('H*', $wkb)) : $wkb;
    }

    /**
     * @param Geometry $geometry
     * @return string
     */
    function writeGeometry($geometry) {
        $this->hasZ = $geometry->hasZ();
        $this->hasM = $geometry->isMeasured();

        $wkb = $this->writer->writeSInt8($this->writer->isBigEndian() ? self::WKB_NDR : self::WKB_XDR);
        $wkb .= $this->writeType($geometry);
        switch ($geometry->geometryType()) {
            case 'Point':
                /** @var Point $geometry */
                $wkb .= $this->writePoint($geometry);
                break;
            case 'LineString':
                /** @var LineString $geometry */
                $wkb .= $this->writeLineString($geometry);
                break;
            case 'Polygon':
                /** @var Polygon $geometry */
                $wkb .= $this->writePolygon($geometry);
                break;
            case 'MultiPoint':
                /** @var MultiPoint $geometry */
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'MultiLineString':
                /** @var MultiLineString $geometry */
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'MultiPolygon':
                /** @var MultiPolygon $geometry */
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'GeometryCollection':
                /** @var GeometryCollection $geometry */
                $wkb .= $this->writeMulti($geometry);
                break;
        }
        return $wkb;
    }

    /**
     * @param Point $point
     * @return string
     */
    function writePoint($point) {
        if ($point->isEmpty()) {
            return $this->writer->writeDouble(NAN) . $this->writer->writeDouble(NAN);
        }
        $wkb = $this->writer->writeDouble($point->x()) . $this->writer->writeDouble($point->y());

        if ($this->hasZ) {
            $wkb .= $this->writer->writeDouble($point->z());
        }
        if ($this->hasM) {
            $wkb .= $this->writer->writeDouble($point->m());
        }
        return $wkb;
    }

    /**
     * @param LineString $line
     * @return string
     */
    function writeLineString($line) {
        // Set the number of points in this line
        $wkb = $this->writer->writeUInt32($line->numPoints());

        // Set the coords
        foreach ($line->getComponents() as $i=>$point) {
            $wkb .= $this->writePoint($point);
        }

        return $wkb;
    }

    /**
     * @param Polygon $poly
     * @return string
     */
    function writePolygon($poly) {
        // Set the number of lines in this poly
        $wkb = $this->writer->writeUInt32($poly->numGeometries());

        // Write the lines
        foreach ($poly->getComponents() as $line) {
            $wkb .= $this->writeLineString($line);
        }

        return $wkb;
    }

    /**
     * @param MultiPoint|MultiPolygon|MultiLineString|GeometryCollection $geometry
     * @return string
     */
    function writeMulti($geometry) {
        // Set the number of components
        $wkb = $this->writer->writeUInt32($geometry->numGeometries());

        // Write the components
        foreach ($geometry->getComponents() as $component) {
            $wkb .= $this->writeGeometry($component);
        }

        return $wkb;
    }

    /**
     * @param Geometry $geometry
     * @param bool $writeSRID
     * @return string
     */
    protected function writeType($geometry, $writeSRID = false) {
        $type = self::$mapType[$geometry->geometryType()];
        // Binary OR to mix in additional properties
        if ($this->hasZ) {
            $type = $type | $this::Z_MASK;
        }
        if ($this->hasM) {
            $type = $type | $this::M_MASK;
        }
        if ($geometry->SRID() && $writeSRID) {
            $type = $type | $this::SRID_MASK;
        }
        return $this->writer->writeUInt32($type) . ($geometry->SRID() && $writeSRID ? $this->writer->writeUInt32($this->SRID) : '');
    }

}
