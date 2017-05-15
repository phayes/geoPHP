<?php

namespace GeoPHPTests;

use GeoPHP\GeoPHP;

class BasicTest extends \PHPUnit_Framework_TestCase
{
    public function testMethods()
    {
        $format = 'gpx';
        $value = file_get_contents(__DIR__ . '/../input/20120702.gpx');
        $geometry = GeoPHP::load($value, $format);

        $methods = [
            ['name' => 'area'],
            ['name' => 'boundary'],
            ['name' => 'getBBox'],
            ['name' => 'centroid'],
            ['name' => 'length'],
            ['name' => 'greatCircleLength', 'argument' => 6378137],
            ['name' => 'haversineLength'],
            ['name' => 'y'],
            ['name' => 'x'],
            ['name' => 'numGeometries'],
            ['name' => 'geometryN', 'argument' => '1'],
            ['name' => 'startPoint'],
            ['name' => 'endPoint'],
            ['name' => 'isRing'],
            ['name' => 'isClosed'],
            ['name' => 'numPoints'],
            ['name' => 'pointN', 'argument' => '1'],
            ['name' => 'exteriorRing'],
            ['name' => 'numInteriorRings'],
            ['name' => 'interiorRingN', 'argument' => '1'],
            ['name' => 'dimension'],
            ['name' => 'geometryType'],
            ['name' => 'SRID'],
            ['name' => 'setSRID', 'argument' => '4326'],
        ];

        foreach ($methods as $method) {
            $argument = null;
            $method_name = $method['name'];
            if (isset($method['argument'])) {
                $argument = $method['argument'];
            }
            $this->_methods_tester($geometry, $method_name, $argument);
        }
    }

    public function _methods_tester($geometry, $method_name, $argument)
    {

        if (!method_exists($geometry, $method_name)) {
            $this->fail("Method " . $method_name . '() doesn\'t exists.');

            return;
        }

        switch ($method_name) {
            case 'y':
            case 'x':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'geometryN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'startPoint':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    //TODO: Add a method startPoint() to MultiLineString.
                    //$this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'endPoint':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    //TODO: Add a method endPoint() to MultiLineString.
                    //$this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'isRing':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'isClosed':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'pointN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    //TODO: Add a method pointN() to MultiLineString.
                    //$this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'exteriorRing':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'numInteriorRings':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'interiorRingN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'setSRID':
                //TODO: The method setSRID() should return TRUE.
                break;
            case 'SRID':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'getBBox':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'centroid':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'length':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertEquals(
                        $geometry->$method_name($argument), (float) '0.11624637315233', 'Failed on ' . $method_name
                    );
                }
                break;
            case 'numGeometries':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'numPoints':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'dimension':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'boundary':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                break;
            case 'greatCircleLength':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotEquals(
                        $geometry->$method_name($argument), '9500.9359867418', 'Failed on ' . $method_name
                    );
                }
                break;
            case 'haversineLength':
            case 'area':
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                break;
            case 'geometryType':
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
                break;
            default:
                $this->assertTrue($geometry->$method_name($argument), 'Failed on ' . $method_name);
        }
    }
}

