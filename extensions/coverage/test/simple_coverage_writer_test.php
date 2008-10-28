<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');
require_once dirname(__FILE__) .'/../simple_coverage_writer.php';
require_once dirname(__FILE__) .'/../coverage_calculator.php';

class SimpleCoverageWriterTest extends UnitTestCase {
  
  function testWriteSummary() {
    $writer = new SimpleCoverageWriter();
    $coverage = array('file' => array(0, 1));
    $untouched = array('missed-file');
    $calc = new CoverageCalculator();
    $variables = $calc->variables($coverage, $untouched);
    $out = fopen("php://memory", 'w');
    $writer->writeSummary($out, $variables);
    rewind($out);
    $actual = stream_get_contents($out);
    $this->assertPattern("/Total Coverage: 50%/", $actual);
    $this->assertPattern("/Total Files Covered: 50%/", $actual);
  }
  
  function testWriteByFile() {
    $writer = new SimpleCoverageWriter();
    $cov = array(3 => 1, 4 => -2); // 2 comments, 1 code, 1 dead  (1-based indexes)
    $out = fopen("php://memory", 'w');
    $file = dirname(__FILE__) .'/sample-code.php';
    $calc = new CoverageCalculator();
    $variables = $calc->coverageByFileVariables($file, $cov); 
    $writer->writeByFile($out, $variables);
    rewind($out);
    $actual = stream_get_contents($out);
    $expected = <<<EXPECTED
  <?php
  // sample code
+ \$x = 1 + 2;
x if (false) echo "dead";
EXPECTED;
    $this->assertEqual($expected, $actual);
  }
  
  function testDecodeLineStyle() {
    $writer = new SimpleCoverageWriter();
    $this->assertEqual('-', $writer->decodeLineStyle('missed'));
    $this->assertEqual('+', $writer->decodeLineStyle('covered'));
    $this->assertEqual('x', $writer->decodeLineStyle('dead'));  
    $this->assertEqual(' ', $writer->decodeLineStyle(''));
    $this->assertEqual(' ', $writer->decodeLineStyle('anything'));
  }
}

?>