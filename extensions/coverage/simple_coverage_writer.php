<?php

require_once dirname(__FILE__) .'/coverage_writer.php';

class SimpleCoverageWriter implements CoverageWriter {

  function __construct() {
    $this->styleCodes = array('dead' => 'x', 'covered' => '+', 'missed' => '-');
  }
  
  function writeSummary($out, $variables) {
    extract($variables);
    $now = date("F j, Y, g:i a");
    $template = <<<TEMPLATE
 Summary
Total Coverage: $totalPercentCoverage%
Total Files Covered: $filesTouchedPercentage% 
Report Generation Date: $now
TEMPLATE;
     fwrite($out, $template);
  }
  
  function writeByFile($out, $variables) {
    foreach ($variables['lines'] as $lineNo => $line) {
      $code = $this->decodeLineStyle($line['lineCoverage']);
      fprintf($out, "%s %s", $code, $line['code']);
    }     
  }
  
  function decodeLineStyle($style) {
    return array_key_exists($style, $this->styleCodes) ? $this->styleCodes[$style] : ' ';
  }
}
?>