<?php
require_once 'Smarty.class.php';

require_once dirname(__FILE__) .'/coverage_utils.php';
require_once dirname(__FILE__) .'/coverage_writer.php';

/**
 * Uses smarty template libary to generate HTML coverage reports
 */
class TemplatedCoverageWriter implements CoverageWriter {
  var $smarty;
  
  function __construct() {
    $smarty = new Smarty();
    $smarty->template_dir = dirname(__FILE__) .'/CoverageTemplates';    
    // create this in current directory, semi-persistant between runs
    $smarty->compile_dir = 'coverage.CoverageTemplates_c';
    CoverageUtils::mkdir($smarty->compile_dir);
    $this->smarty = $smarty;
  }
  
  function writeSummary($out, $variables) {
    $this->smarty->assign($variables);
    $report = $this->smarty->fetch('index.tpl');
    fwrite($out, $report);
  }
  
  function writeByFile($out, $variables) {
    $this->smarty->assign($variables);      
    $report = $this->smarty->fetch('file.tpl');      
    fwrite($out, $report);
  }
}
?>