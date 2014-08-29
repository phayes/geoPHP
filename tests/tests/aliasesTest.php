<?php
require_once('../geoPHP.inc');
class AliasesTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testAliases() {
    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        echo "\nloading: " . $file . " for format: " . $format;
        $geometry = geoPHP::load($value, $format);

        $aliases = array(
          array('name' => 'getCentroid'),
          array('name' => 'getArea'),
          array('name' => 'getX'),
          array('name' => 'getY'),
          array('name' => 'getGeos'),
          array('name' => 'getGeomType'),
          array('name' => 'getSRID'),
          array('name' => 'asText'),
          array('name' => 'asBinary'),
        );

        foreach($aliases as $alias) {
          $argument = NULL;
          $alias_name = $alias['name'];
          if (isset($alias['argument'])) {
            $argument = $alias['argument'];
          }

          switch ($alias_name) {
            case 'getSRID':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              break;
            case 'getGeos':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              break;
            case 'getX':
            case 'getY':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              break;
            case 'getArea':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              break;
            case 'getCentroid':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              }
              break;
            case 'asText':
            case 'asBinary':
            case 'getGeomType':
              $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
              break;
            default:
              $this->assertTrue($geometry->$alias_name($argument), 'Failed on ' . $alias_name .' (test file: ' . $file . ')');
          }
        }

      }
    }
  }

}
