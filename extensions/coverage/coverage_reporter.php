<?php
require_once 'Smarty.class.php';

/**
 * Take aggregated coverage data and generate reports from it using smarty
 * templates
 */
class CoverageReporter {  
  var $coverage;
  var $untouched;
  var $reportDir;
  var $title = 'Coverage';
  
  function __construct() {
    $smarty = new Smarty();
    $smarty->template_dir = dirname(__FILE__) .'/CoverageTemplates';    
    // create this in current directory, semi-persistant between runs
    $smarty->compile_dir = 'coverage.CoverageTemplates_c';
    CoverageReporter::mkdir($smarty->compile_dir);
    $this->smarty = $smarty;
  }
    
  static function mkdir($dir) {
    if (!file_exists($dir)) {
      mkdir($dir, 0777, True);
    } else {
      if (!is_dir($dir)) {
        throw new Exception($dir .' exists as a file, not a directory');
      }
    }
  }
  
  function generateSummaryReport($out) {
    $variables = $this->variables();
    $variables['title'] = $this->title;
    $this->smarty->assign($variables);
    $report = $this->smarty->fetch('index.tpl');
    fwrite($out, $report);
  }
  
  function generate() {    
    $this->mkdir($this->reportDir);
    
    $index = $this->reportDir .'/index.html';
    $hnd = fopen($index, 'w');
    $this->generateSummaryReport($hnd);       
    fclose($hnd); 
    
    foreach ($this->coverage as $file => $cov) {
      $byFile = $this->reportDir .'/'. self::reportFilename($file);
      $byFileHnd = fopen($byFile, 'w');
      $this->generateCoverageByFile($byFileHnd, $file, $cov);
      fclose($byFileHnd);
    }
    
    echo "generated report $index\n";    
  }  
  
  function generateCoverageByFile($out, $file, $cov) {
    $variables = $this->coverageByFileVariables($file, $cov);
    $variables['title'] = $this->title .' - '. $file;
    $this->smarty->assign($variables);      
    $report = $this->smarty->fetch('file.tpl');      
    fwrite($out, $report);      
  }
  
  static function reportFilename($filename) {
    return preg_replace('|[/\\\\]|', '_', $filename) . '.html';    
  }
  
  static function lineCoverageCodeToStyleClass($coverage, $line) {
    if (!array_key_exists($line, $coverage)) {
      return "comment";
    }
    $code = $coverage[$line];
    if (empty($code)) {
      return "comment";      
    }
    switch ($code) {
      case -1:
        return "missed";
      case -2:
        return "dead";
    }
    
    return "covered";
  }
  
  function coverageByFileVariables($file, $coverage) {
    $hnd = fopen($file, 'r');
    if ($hnd == null) {
      throw new Exception("File $file is missing");
    }
    $lines = array();
    for ($i = 1; !feof($hnd); $i++) {
      $line = fgets($hnd);
      $lineCoverage = self::lineCoverageCodeToStyleClass($coverage, $i);
      $lines[$i] = array('lineCoverage' => $lineCoverage, 'code' => $line);
    }
    
    fclose($hnd);
    
    $var = compact('file', 'lines', 'coverage');
    return $var;
  }
  
  function totalLoc($total, $coverage) {
    return $total + sizeof($coverage);
  }
  
  function lineCoverage($total, $line) {
    # NOTE: counting dead code as covered, as it's almost always an executable line
    # strange artifact of xdebug or underlying system
    return $total + ($line > 0 || $line == -2 ? 1 : 0);
  }
  
  function totalCoverage($total, $coverage) {
    return $total + array_reduce($coverage, array(&$this, "lineCoverage"));
  }
  
  function percentCoverageByFile($coverage, $file, $results) {
    $byFileReport = self::reportFilename($file);
    
    $loc = sizeof($coverage);
    if ($loc == 0)
      return 0;
    $lineCoverage = array_reduce($coverage, array(&$this, "lineCoverage"));
    $percentage = 100 * ($lineCoverage / $loc);
    $results[0][$file] = array('byFileReport' => $byFileReport, 'percentage' => $percentage); 
  }  
  
  function variables() {
    $coverageByFile = array();     
    array_walk($this->coverage, array(&$this, "percentCoverageByFile"), array(&$coverageByFile));
    
    $totalLoc = array_reduce($this->coverage, array(&$this, "totalLoc"));
    
    if ($totalLoc > 0) {
      $totalLinesOfCoverage = array_reduce($this->coverage, array(&$this, "totalCoverage"));
      $totalPercentCoverage = 100 * ($totalLinesOfCoverage / $totalLoc);
    }
    
    $untouchedPercentageDenominator = sizeof($this->coverage) + sizeof($this->untouched);
    if ($untouchedPercentageDenominator > 0) {
        $filesTouchedPercentage = 100 * sizeof($this->coverage) / $untouchedPercentageDenominator;
    }
    
    $var = compact('coverageByFile', 'totalPercentCoverage', 'totalLoc', 'totalLinesOfCoverage', 'filesTouchedPercentage');
    $var['untouched'] = $this->untouched;
    return $var;    
  }
}



?>