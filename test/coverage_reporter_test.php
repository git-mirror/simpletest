<?php
require_once dirname(__FILE__) .'/../coverage_reporter.php';
require_once dirname(__FILE__) .'/../autorun.php';

class CoverageReporterTest extends UnitTestCase {
  
  function testInitialization() {
    new CoverageReporter();
  }
  
  function testVariables() {
    $reporter = new CoverageReporter();
    $reporter->coverage = array('file' => array(1,1,1,1));
    $reporter->untouched = array('missed-file');
    $variables = $reporter->variables();
    $this->assertEqual(4, $variables['totalLoc']);    
    $this->assertEqual(100, $variables['totalPercentCoverage']);            
    $this->assertEqual(4, $variables['totalLinesOfCoverage']);    
    $expected = array('file' => array('byFileReport' => 'file.html', 'percentage' => 100));
    $this->assertEqual($expected, $variables['coverageByFile']);
    $this->assertEqual(50, $variables['filesTouchedPercentage']);
    $this->assertEqual($reporter->untouched, $variables['untouched']);
  }
  
  function testGenerateSummaryReport() {
    $reporter = new CoverageReporter();
    $reporter->coverage = array('file' => array(0, 1));
    $reporter->untouched = array('missed-file');
    $out = fopen("php://memory", 'w');
    $reporter->generateSummaryReport($out);
    $dom = self::dom($out);
    $totalPercentCoverage = $dom->elements->xpath("//span[@class='totalPercentCoverage']");
    $this->assertEqual('50%', (string)$totalPercentCoverage[0]);
        
    $fileLinks = $dom->elements->xpath("//a[@class='byFileReportLink']");
    $fileLinkAttr = $fileLinks[0]->attributes();
    $this->assertEqual('file.html', $fileLinkAttr['href']);
    $this->assertEqual('file', (string)($fileLinks[0]));
    
    $untouchedFile = $dom->elements->xpath("//span[@class='untouchedFile']");
    $this->assertEqual('missed-file', (string)$untouchedFile[0]);
  }
  
  function testGenerateCoverageByFile() {
    $reporter = new CoverageReporter();
    $cov = array(3 => 1, 4 => -2); // 2 comments, 1 code, 1 dead  (1-based indexes)
    $out = fopen("php://memory", 'w');
    $file = dirname(__FILE__) .'/sample-code.php';
    $reporter->generateCoverageByFile($out, $file, $cov);
    $dom = self::dom($out);    
    $title = (string)($dom->head->title);
    $this->assertEqual("Coverage - $file", $title);

    $cells = $dom->elements->xpath("//table[@id='code']/tbody/tr/td/span");
    $this->assertEqual("comment code", self::getAttribute($cells[1], 'class'));  
    $this->assertEqual("comment code", self::getAttribute($cells[3], 'class'));
    $this->assertEqual("covered code", self::getAttribute($cells[5], 'class'));
    $this->assertEqual("dead code", self::getAttribute($cells[7], 'class'));
  }

  static function getAttribute($element, $attribute) {
    $a = $element->attributes();
    return $a[$attribute];
  }
 
  static function dom($stream) {
    rewind($stream);
    $actual = stream_get_contents($stream);
    $html = DOMDocument::loadHTML($actual);
    return simplexml_import_dom($html);    
  }
 
  function testMkdir() {
    CoverageReporter::mkdir(dirname(__FILE__));
    try {
      CoverageReporter::mkdir(__FILE__);
      $this->fail("Should give error about cannot create dir of a file");
    } catch (Exception $expected) {      
    }
  }
 
  function testPercentageCoverageByFile() {
    $reporter = new CoverageReporter();
    $coverage = array(0,0,0,1,1,1);
    $pct = array(); 
    $reporter->percentCoverageByFile($coverage, 'file', array(&$pct));
    $this->assertEqual(50, $pct['file']['percentage']);
    $this->assertEqual('file.html', $pct['file']['byFileReport']);
  }  

  function testTotalLoc() {
    $reporter = new CoverageReporter();
    $this->assertEqual(13, $reporter->totalLoc(10, array(1,2,3)));    
  }

  function testreportFilename() {
      $this->assertEqual("parula.php.html", CoverageReporter::reportFilename("parula.php"));
      $this->assertEqual("warbler_parula.php.html", CoverageReporter::reportFilename("warbler/parula.php"));
      $this->assertEqual("warbler_parula.php.html", CoverageReporter::reportFilename("warbler\\parula.php"));
  }

  function testLineCoverage() {
    $reporter = new CoverageReporter();
    $this->assertEqual(10, $reporter->lineCoverage(10, -1));    
    $this->assertEqual(10, $reporter->lineCoverage(10, 0));    
    $this->assertEqual(11, $reporter->lineCoverage(10, 1));    
  }

  function testTotalCoverage() {
    $reporter = new CoverageReporter();
    $this->assertEqual(11, $reporter->totalCoverage(10, array(-1,1)));    
  }
}


?>