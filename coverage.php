<?php

require_once 'DB/sqlite.php';

/**
 * Orchestrates code coverage both in this thread and in subthread under apache
 * Assumes this is running on same machine as apache.
 */
class CodeCoverage  {  
  var $log;
  var $root;
  var $includes;
  var $excludes;
  var $directoryDepth;
  var $maxDirectoryDepth = 20; // reasonable, otherwise arbitrary
  var $title = "Code Coverage";
  
  # NOTE: This assumes all code shares the same current working directory. 
  var $settingsFile = 'code-coverage-settings.dat';
  
  var $isMainThread;
  static $instance;
  
  function run($runner, $files) {
    $runner->initialize(1000);
    $this->resetLog();
    $this->startCoverage();
    $this->writeSettings();
    $results = $runner->run($files);
    $this->stopCoverage();
    $this->writeUntouched();
    return $results;    
  }
  
  function writeUntouched() {
    $touched = array_flip($this->getTouchedFiles());
    $untouched = array();
    $this->getUntouchedFiles($untouched, $touched, '.', '.');
    $this->includeUntouchedFiles($untouched);
  }
  
  function &getTouchedFiles() {
    $handler = new CoverageDataHandler($this->log);
    $touched = $handler->getFilenames();
    return $touched;
  }
  
  function includeUntouchedFiles($untouched) {
    $handler = new CoverageDataHandler($this->log);
    foreach ($untouched as $file) {
      $handler->writeUntouchedFile($file);
    }
  }
  
  function getUntouchedFiles(&$untouched, $touched, $parentPath, $rootPath, $directoryDepth = 1) {
    $parent = opendir($parentPath);
    while ($file = readdir($parent)) {
      $path = "$parentPath/$file";
      if (is_dir($path)) {
        if ($file != '.' && $file != '..') {          
          if ($this->isDirectoryIncluded($path, $directoryDepth)) {
            $this->getUntouchedFiles($untouched, $touched, $path, $rootPath, $directoryDepth + 1);
          }
        }
      }
      else if ($this->isFileIncluded($path)) {
        $relativePath = CoverageDataHandler::ltrim($rootPath .'/', $path);
        if (!array_key_exists($relativePath, $touched)) {
          $untouched[] = $relativePath;
        }
      }
    }
    fclose($parent);
  }
  
  function resetLog() {
    $new_file = fopen($this->log, "w");
    if (!$new_file) {
      throw new Exception("Could not create ". $this->log);      
    }
    fclose($new_file);
    if (!chmod($this->log, 0666)) {   
      throw new Exception("Could not change ownership on file  ". $this->log);     
    }
    $handler = new CoverageDataHandler($this->log);
    $handler->createSchema();
  }
  
  function startCoverage() {
    $this->root = getcwd();
    if(!extension_loaded("xdebug")) {
      throw new Exception("Could not load xdebug extension");
    };   
echo "Starting coverage\n";
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
  }
  
  function stopCoverage() {
echo "Writing coverage\n";
    $cov = xdebug_get_code_coverage();
    $this->filter($cov);
    $data = new CoverageDataHandler($this->log);
    chdir($this->root);
    $data->write($cov);
    unset($data); // release sqlite connection 
    xdebug_stop_code_coverage();
    // make sure we wind up on same current working directory, otherwise
    // coverage handler writer doesn't know what directory to chop off
    chdir($this->root);
  }
  
  function readSettings() {
    $file = getcwd() .'/'. $this->settingsFile;
    if (file_exists($file)) {
      $this->setSettings(file_get_contents($file));
    } else {
      error_log("could not find file ". $file);
    }
  }
  
  function writeSettings() {
    file_put_contents($this->settingsFile, $this->getSettings());
  }
  
  function getSettings() {
    $data = array(
    	'log' => realpath($this->log), 
    	'includes' => $this->includes, 
    	'excludes' => $this->excludes);
    return serialize($data);
  }
  
  function setSettings($settings) {
    $data = unserialize($settings);
    $this->log = $data['log'];
    $this->includes = $data['includes'];
    $this->excludes = $data['excludes'];
  }
  
  function filter(&$coverage) {
    foreach ($coverage as $file => $line) {
      if (!$this->isFileIncluded($file)) {
        unset($coverage[$file]);
      }
    }
  }

  function isFileIncluded($file)  {
    if (isset($this->excludes)) {
      foreach ($this->excludes as $path) {
        if (preg_match('|' . $path . '|', $file)) {
          return False;
        }
      }
    }
    
    if (isset($this->includes)) {      
      foreach ($this->includes as $path) {
        if (preg_match('|' . $path . '|', $file)) {
          return True; 
        }
      }
      return False;
    }

    return True;
  }
  
  function isDirectoryIncluded($dir, $directoryDepth)  {
    if ($directoryDepth >= $this->maxDirectoryDepth) {
      return false;
    }    
    if (isset($this->excludes)) {
      foreach ($this->excludes as $path) {
        if (preg_match('|' . $path . '|', $dir)) {
          return False;
        }
      }
    }

    return True;
  }
  
  static function getMainInstance() {
    self::$instance = CodeCoverage::getInstance();
    self::$instance->isMainThread = True;
    return self::$instance;
  }

  static function getExternalProcessInstance() {
    $coverage = CodeCoverage::getInstance();
    $coverage->readSettings();
    return $coverage;
  }
  
  static function isCoverageOnForExternalProcess() {
    $coverage = self::getInstance();
    if ($coverage->isMainThread) { 
      return False;
    }
    $coverage->readSettings();
    if (empty($coverage->log) || !file_exists($coverage->log)) {
      trigger_error('No coverage log');
      return False; 
    }
    return True;
  }
  
  static function getInstance() {
    if (self::$instance == NULL) {
      self::$instance = new CodeCoverage();
    }
    return self::$instance;
  }
}


/**
 * Persists code coverage data into SQLite database and aggregate data for convienent
 * interpretation in report generator.  Be sure to not to keep an instance longer
 * than you have, otherwise you risk overwriting database edits from another process
 * also trying to make updates.
 */
class CoverageDataHandler {
  
  var $db;
  
  function __construct($filename) {
    $this->db = new SQLiteDatabase($filename);
    if (empty($this->db)) {
      throw new Exception("Could not create sqlite db ". $filename);
    }
  }

  function createSchema() {
    $this->db->queryExec("create table untouched (filename text)");
    $this->db->queryExec("create table coverage (name text, coverage text)");
  }
  
  function &getFilenames() {
    $filenames = array();
    $cursor = $this->db->unbufferedQuery("select distinct name from coverage");
    while ($row = $cursor->fetch()) {
      $filenames[] = $row[0];
    }
    
    return $filenames;
  }
   
  function write($coverage) {
    foreach ($coverage as $file => $lines) {
      $coverageStr = serialize($lines);
      $relativeFilename = self::ltrim(getcwd() . '/', $file);
      $sql = "insert into coverage (name, coverage) values ('$relativeFilename', '$coverageStr')";
      $this->db->queryExec($sql);
    }
  }
  
  function read() {
    $coverage = array_flip($this->getFilenames());
    foreach($coverage as $file => $garbage) {
      $coverage[$file] = $this->readFile($file);   
    }
    return $coverage;    
  }
  
  function &readFile($file) {        
    $sql = "select coverage from coverage where name = '$file'";
    $aggregate = array();
    $result = $this->db->query($sql);
    while ($result->valid()) {      
      $row = $result->current();      
      $this->aggregateCoverage($aggregate, unserialize($row[0])); 
      $result->next();
    }

    return $aggregate;
  }
  
  function aggregateCoverage(&$total, $next) {
    foreach ($next as $lineno => $code) {      
      if (!isset($total[$lineno])) {
        $total[$lineno] = $code;
      } else {
        $total[$lineno] = $this->aggregateCoverageCode($total[$lineno], $code);
      }
    }
  }
  
  function aggregateCoverageCode($code1, $code2) {
    switch($code1) {
      case -2: return -2;
      case -1: return $code2;
      default:
        switch ($code2) {
          case -2: return -2;
          case -1: return $code1;
        }
    }
    return $code1 + $code2;
  }
  
  static function ltrim($cruft, $pristine) {
    if(stripos($pristine, $cruft) === 0) {
      return substr($pristine, strlen($cruft));
    }
    return $pristine;
  }
  
  function writeUntouchedFile($file) {
    $relativeFile = CoverageDataHandler::ltrim('./', $file);
    $sql = "insert into untouched values ('$relativeFile')";    
    $this->db->queryExec($sql);
  }
  
  function &readUntouchedFiles() {
    $untouched = array();
    $result = $this->db->query("select filename from untouched order by filename");
    while ($result->valid()) {
        $row = $result->current();
        $untouched[] = $row[0];
        $result->next();
    }     
    
    return $untouched;
  }
}
