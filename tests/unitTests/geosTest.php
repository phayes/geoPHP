<?php

use \geoPHP\geoPHP;

class GeosTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testGeos() {
    if (!geoPHP::geosInstalled()) {
      $this->markTestSkipped('GEOS not installed');
      return;
    }

    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        if ($parts[0] == 'countries_ne_110m') {
          // Due to a bug in GEOS we have to skip some tests
          // It drops TopologyException for valid geometries
          // https://trac.osgeo.org/geos/ticket/737
          continue;
        }

        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        echo "\nloading: " . $file . " for format: " . $format;
        $geometry = geoPHP::load($value, $format);

        $geosMethods = array(
          array('name' => 'geos'),
          array('name' => 'setGeos', 'argument' => $geometry->geos()),
          array('name' => 'pointOnSurface'),
          array('name' => 'equals', 'argument' => $geometry),
          array('name' => 'equalsExact', 'argument' => $geometry),
          array('name' => 'relate', 'argument' => $geometry),
          array('name' => 'checkValidity'),
          array('name' => 'isSimple'),
          array('name' => 'buffer', 'argument' => '10'),
          array('name' => 'intersection', 'argument' => $geometry),
          array('name' => 'convexHull'),
          array('name' => 'difference', 'argument' => $geometry),
          array('name' => 'symDifference', 'argument' => $geometry),
          array('name' => 'union', 'argument' => $geometry),
          array('name' => 'simplify', 'argument' => '0'),
          array('name' => 'disjoint', 'argument' => $geometry),
          array('name' => 'touches', 'argument' => $geometry),
          array('name' => 'intersects', 'argument' => $geometry),
          array('name' => 'crosses', 'argument' => $geometry),
          array('name' => 'within', 'argument' => $geometry),
          array('name' => 'contains', 'argument' => $geometry),
          array('name' => 'overlaps', 'argument' => $geometry),
          array('name' => 'covers', 'argument' => $geometry),
          array('name' => 'coveredBy', 'argument' => $geometry),
          array('name' => 'distance', 'argument' => $geometry),
          array('name' => 'hausdorffDistance', 'argument' => $geometry),
        );

        foreach($geosMethods as $method) {
          $argument = NULL;
          $method_name = $method['name'];
          if (isset($method['argument'])) {
            $argument = $method['argument'];
          }
          $error_message = 'Failed on "' . $method_name .'" method with test file "' . $file . '"';
          
          // GEOS don't like empty points
          if ($geometry->geometryType() == 'Point' && $geometry->isEmpty()) {
            continue;
          }

          switch ($method_name) {
            case 'geos':
              $this->assertInstanceOf('GEOSGeometry', $geometry->$method_name($argument), $error_message);
              break;
            case 'equals':
            case 'equalsExact':
            case 'disjoint':
            case 'touches':
            case 'intersects':
            case 'crosses':
            case 'within':
            case 'contains':
            case 'overlaps':
            case 'covers':
            case 'coveredBy':
              $this->assertInternalType('bool', $geometry->$method_name($argument), $error_message);
              break;
            case 'pointOnSurface':
            case 'buffer':
            case 'intersection':
            case 'convexHull':
            case 'difference':
            case 'symDifference':
            case 'union':
            case 'simplify':
              $this->assertInstanceOf('geoPHP\\Geometry\\Geometry', $geometry->$method_name($argument), $error_message);
              break;
            case 'distance':
            case 'hausdorffDistance':
              $this->assertInternalType('double', $geometry->$method_name($argument), $error_message);
              break;
            case 'relate':
              $this->assertRegExp('/[0-9TF]{9}/', $geometry->$method_name($argument), $error_message);
              break;
            case 'checkValidity':
              $this->assertArrayHasKey('valid', $geometry->$method_name($argument), $error_message);
              break;
            case 'isSimple':
              if ($geometry->geometryType() == 'GeometryCollection') {
                $this->assertNull($geometry->$method_name($argument), $error_message);
              } else {
                $this->assertNotNull($geometry->$method_name($argument), $error_message);
              }
              break;
            default:
          }
        }

      }
    }
  }

}
