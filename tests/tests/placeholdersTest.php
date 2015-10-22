<?php
require_once('../geoPHP.inc');
class PlaceholdersTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testPlaceholders() {
    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        echo "\nloading: " . $file . " for format: " . $format;
        $geometry = geoPHP::load($value, $format);

        $placeholders = array(
          array('name' => 'hasZ'),
          array('name' => 'is3D'),
          array('name' => 'isMeasured'),
          array('name' => 'isEmpty'),
          array('name' => 'coordinateDimension'),
          array('name' => 'z'),
          array('name' => 'm'),
        );

        foreach($placeholders as $method) {
          $argument = NULL;
          $method_name = $method['name'];
          if (isset($method['argument'])) {
            $argument = $method['argument'];
          }

          switch ($method_name) {
            case 'hasZ':
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
            case 'm':
            case 'z':
            case 'coordinateDimension':
            case 'isEmpty':
            case 'isMeasured':
            case 'is3D':
          }
        }

      }
    }

  }
}
