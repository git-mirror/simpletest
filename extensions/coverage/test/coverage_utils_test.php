<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');
require_once dirname(__FILE__) .'/../coverage_utils.php';

class CoverageUtilsTest extends UnitTestCase {
 
  function testMkdir() {
    CoverageUtils::mkdir(dirname(__FILE__));
    try {
      CoverageUtils::mkdir(__FILE__);
      $this->fail("Should give error about cannot create dir of a file");
    } catch (Exception $expected) {      
    }
  }
}

?>