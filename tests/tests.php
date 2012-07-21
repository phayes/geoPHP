<?php
require_once('simpletest/autorun.php');
require_once('../geoPHP.inc');

class AllTests extends TestSuite {

  function allTests() {
    $this->TestSuite('All tests');
    $this->addFile('tests/methods.test');
    //$this->addFile('tests/aliases.test');
    //$this->addFile('tests/geos.test');
    //$this->addFile('tests/placeholders.test');
  }
}
