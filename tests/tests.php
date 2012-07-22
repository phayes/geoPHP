<?php
require_once('simpletest/autorun.php');
require_once('../geoPHP.inc');

class AllTests extends TestSuite {
  function __construct() {
    parent::__construct();
    $this->collect(dirname(__FILE__) . '/tests',
      new SimplePatternCollector('/.test/'));
  }
}
