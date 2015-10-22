<?php
require_once('../geoPHP.inc');
class GeosTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testGeos() {
    if (!geoPHP::geosInstalled()) {
      echo "Skipping GEOS -- not installed";
      return;
    }
    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        echo "\nloading: " . $file . " for format: " . $format;
        $geometry = geoPHP::load($value, $format);

        $geosMethods = array(
          array('name' => 'geos'),
          array('name' => 'setGeos', 'argument' => $geometry->geos()),
          array('name' => 'PointOnSurface'),
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

          switch ($method_name) {
            case 'isSimple':
            case 'equals':
            case 'geos':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
              break;
            default:
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name .' (test file: ' . $file . ')');
              }
          }
        }

      }
    }
  }

}
