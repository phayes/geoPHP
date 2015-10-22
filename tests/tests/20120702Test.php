<?php
require_once('../geoPHP.inc');
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

  function _methods_tester($geometry, $method_name, $argument) {

    if (!method_exists($geometry, $method_name)) {
      $this->fail("Method ".$method_name.'() doesn\'t exists.');
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
          $this->assertEquals($geometry->$method_name($argument), (float) '0.11624637315233', 'Failed on ' . $method_name);
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
          $this->assertNotEquals($geometry->$method_name($argument), '9500.9359867418', 'Failed on ' . $method_name);
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

