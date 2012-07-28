<?php
require_once('../geoPHP.inc');
require_once('PHPUnit/Autoload.php');

class AliasesTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testAliases() {
    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
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
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              break;
            case 'getGeos':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              break;
            case 'getX':
            case 'getY':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              break;
            case 'getArea':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              break;
            case 'getCentroid':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              }
              break;
            case 'asText':
            case 'asBinary':
            case 'getGeomType':
              $this->assertNotNull($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
              break;
            default:
              $this->assertTrue($geometry->$alias_name($argument), 'Failed on ' . $alias_name);
          }
        }

      }
    }
  }

}
