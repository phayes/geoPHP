<?php
require_once('../geoPHP.inc');

class EWKBTests extends PHPUnit_Framework_TestCase {

  function setUp() {

  }

  function testWithoutSRID() {
    $geom = geoPHP::load('POINT(0 20)');
    $this->assertEquals("\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x34\x40", $geom->out('ewkb'));
    $this->assertEquals("010100000000000000000000000000000000003440", $geom->out('ewkb', true));
  }

  function testWithSRID() {
    $geom = geoPHP::load('SRID=4326; POINT(0 20)');
    $this->assertEquals("\x01\x01\x00\x00\x20\xE6\x10\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x34\x40", $geom->out('ewkb'));
    $this->assertEquals("0101000020e610000000000000000000000000000000003440", $geom->out('ewkb', true));
  }
}
