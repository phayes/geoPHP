<?php

use \geoPHP\geoPHP;
use \geoPHP\Geometry\Geometry;

// FIXME file 20120702.gpx contains one MultiLineString but _method_tester() also wants to test Points and LineStrings (ie does nothing)

class Tests_20120702 extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testMethods() {
    $format = 'gpx';
    $value = file_get_contents('./input/20120702.gpx');
    $geometry = geoPHP::load($value, $format);

    $methods = array(
      array('name' => 'area'),
      array('name' => 'boundary'),
      array('name' => 'getBBox'),
      array('name' => 'centroid'),
      array('name' => 'length'),
      array('name' => 'greatCircleLength', 'argument' => 6378137),
      array('name' => 'haversineLength'),
      array('name' => 'y'),
      array('name' => 'x'),
      array('name' => 'numGeometries'),
      array('name' => 'geometryN', 'argument' => '1'),
      array('name' => 'startPoint'),
      array('name' => 'endPoint'),
      array('name' => 'isRing'),
      array('name' => 'isClosed'),
      array('name' => 'numPoints'),
      array('name' => 'pointN', 'argument' => '1'),
      array('name' => 'exteriorRing'),
      array('name' => 'numInteriorRings'),
      array('name' => 'interiorRingN', 'argument' => '1'),
      array('name' => 'dimension'),
      array('name' => 'geometryType'),
      array('name' => 'SRID'),
      array('name' => 'setSRID', 'argument' => '4326'),
    );

    foreach($methods as $method) {
      $argument = NULL;
      $method_name = $method['name'];
      if (isset($method['argument'])) {
        $argument = $method['argument'];
      }
      $this->_methods_tester($geometry, $method_name, $argument);
    }
  }

  /**
   * @param Geometry $geometry
   * @param $method_name
   * @param $argument
   */
  function _methods_tester($geometry, $method_name, $argument) {

    if (!method_exists($geometry, $method_name)) {
      $this->fail("Method ".$method_name.'() doesn\'t exists.');
      return;
    }

    $failedOnMessage = $geometry->geometryType() . ' failed on ' . $method_name ;

    switch ($method_name) {
      case 'y':
      case 'x':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'geometryN':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'startPoint':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'endPoint':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'isRing':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'isClosed':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'pointN':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'exteriorRing':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'numInteriorRings':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'interiorRingN':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'setSRID':
        //TODO: The method setSRID() should return TRUE.
        break;
      case 'SRID':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'getBBox':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'centroid':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'length':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals($geometry->$method_name($argument), 0, $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertEquals($geometry->$method_name($argument), (float) '0.11624637315233', $failedOnMessage);
        }
        break;
      case 'numGeometries':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'numPoints':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals($geometry->$method_name($argument), 1, $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'dimension':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals($geometry->$method_name($argument), 0, $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertEquals($geometry->$method_name($argument), 1, $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertEquals($geometry->$method_name($argument), 1, $failedOnMessage);
        }
        break;
      case 'boundary':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'greatCircleLength':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals($geometry->$method_name($argument), 0, $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotEquals($geometry->$method_name($argument), '9500.9359867418', $failedOnMessage);
        }
        break;
      case 'haversineLength':
      case 'area':
        $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        break;
      case 'geometryType':
        $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        break;
      default:
        $this->assertTrue($geometry->$method_name($argument), $failedOnMessage);
    }
  }
}

