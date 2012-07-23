<?php
require_once('../geoPHP.inc');
require_once('PHPUnit/Autoload.php');

class MethodsTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testMethods() {
    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        $geometry = geoPHP::load($value, $format);

        $methods = array(
          array('name' => 'area'),
          array('name' => 'boundary'),
          array('name' => 'getBBox'),
          array('name' => 'centroid'),
          array('name' => 'length'),
          array('name' => 'greatCircleLength'),
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

        $this->_methods_tester_with_geos($geometry);
      }
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
          $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
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
      case 'haversineLength':
        //TODO: Check if output is a float >= 0.
        //TODO: Sometimes haversineLength() returns NAN, needs to check why.
        break;
      case 'greatCircleLength':
      case 'area':
        $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
        break;
      case 'geometryType':
        $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
        break;
      case 'setSRID':
        //TODO: The method setSRID() should return TRUE.
        break;
      default:
        $this->assertTrue($geometry->$method_name($argument), 'Failed on ' . $method_name);
    }

  }

  function _methods_tester_with_geos($geometry) {
    // Cannot test methods if GEOS is not intstalled
    if (!geoPHP::geosInstalled()) return;

    $methods = array(
      //'boundary', //@@TODO: Uncomment this and fix errors
      'envelope',   //@@TODO: Testing reveales errors in this method -- POINT vs. POLYGON
      'getBBox',
      'x',
      'y',
      'startPoint',
      'endPoint',
      'isRing',
      'isClosed',
      'numPoints',
    );

    foreach ($methods as $method) {
      // Turn GEOS on
      geoPHP::geosInstalled(TRUE);
      $geos_result = $geometry->$method();

      // Turn GEOS off
      geoPHP::geosInstalled(FALSE);
      $norm_result = $geometry->$method();

      // Turn GEOS back On
      geoPHP::geosInstalled(TRUE);

      $geos_type = gettype($geos_result);
      $norm_type = gettype($norm_result);

      if ($geos_type != $norm_type) {
        $this->fail('Type mismatch on '.$method);
        $this->dump($geos_type);
        $this->dump($norm_type);
        continue;
      }

      // Now check base on type
      if ($geos_type == 'object') {
        $haus_dist = $geos_result->hausdorffDistance(geoPHP::load($norm_result->out('wkt'),'wkt'));

        // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
        // Using Pythagorean theorem
        $bb = $geos_result->getBBox();
        $scale = sqrt((($bb['maxy'] - $bb['miny'])^2) + (($bb['maxx'] - $bb['minx'])^2));

        // The difference in the output of GEOS and native-PHP methods should be less than 0.5 scaled haustorff units
        if ($haus_dist / $scale > 0.5) {
          $this->fail('Output mismatch on '.$method);
          $this->dump('GEOS : ');
          $this->dump($geos_result->out('wkt'));
          $this->dump('NORM : ');
          $this->dump($norm_result->out('wkt'));
          continue;
        }
      }

      if ($geos_type == 'boolean' || $geos_type == 'string') {
        if ($geos_result !== $norm_result) {
          $this->fail('Output mismatch on '.$method);
          $this->dump('GEOS : ');
          $this->dump((string) $geos_result);
          $this->dump('NORM : ');
          $this->dump((string) $norm_result);
          continue;
        }
      }

      //@@TODO: Run tests for output of types arrays and float
      //@@TODO: centroid function is non-compliant for collections and strings
    }
  }
}
