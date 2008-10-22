<?php
require_once(dirname(__FILE__) . '/coverage.php');
require_once(dirname(__FILE__) . '/coverage_reporter.php');

$log = 'coverage.sqlite';

function coverage() {
  global $log;
  $cc = CodeCoverage::getMainInstance();
  $cc->log = $log;
  $cc->includes = array('.*\.php$');
  $cc->excludes = array('.*/test/.*', '.*Smarty.*', '.*DB.common.php$', '.*sqlite.php$', '.*unit_tests.php$', '.*PEAR.php$');
  $cc->maxDirectoryDepth = 1;
  return $cc;  
}

switch ($_SERVER['argv'][1]) {
  case "open":
    $cc = coverage();
    $cc->resetLog();
    $cc->writeSettings();
    break;
  case "close":
    $cc = coverage();
    $cc->writeUntouched();
    break;
  case "report":
    $report = new CoverageReporter();
    $report->log = $log;
    $report->reportDir = 'coverage-report';
    $report->title = "Simpletest Coverage";
    $handler = new CoverageDataHandler($log);
    $report->coverage = $handler->read();
    $report->untouched = $handler->readUntouchedFiles();
    $report->generate();
    break;
  default:
    echo "Allowed values 'open', 'close' and 'report'\n";
    exit (1);
}
