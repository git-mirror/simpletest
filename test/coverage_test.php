<?php
require_once dirname(__FILE__) .'/../coverage.php';
require_once dirname(__FILE__) .'/../mock_objects.php';
require_once dirname(__FILE__) .'/../autorun.php';

class CodeCoverageTest extends UnitTestCase {
  
  function testIsFileIncluded() {
    $coverage = new CodeCoverage();
    $this->assertTrue($coverage->isFileIncluded('aaa'));
    $coverage->includes = array('a');
    $this->assertTrue($coverage->isFileIncluded('aaa'));
    $coverage->includes = array('x');
    $this->assertFalse($coverage->isFileIncluded('aaa'));
    $coverage->excludes = array('aa');
    $this->assertFalse($coverage->isFileIncluded('aaa'));
  }
  
  function testIsFileIncludedRegexp() {
    $coverage = new CodeCoverage();
    $coverage->includes = array('modules/.*\.php$');
    $coverage->excludes = array('bad-bunny.php');    
    $this->assertFalse($coverage->isFileIncluded('modules/a.test'));
    $this->assertFalse($coverage->isFileIncluded('modules/bad-bunny.test'));
    $this->assertTrue($coverage->isFileIncluded('modules/test.php'));
    $this->assertFalse($coverage->isFileIncluded('module-bad/good-bunny.php'));
    $this->assertTrue($coverage->isFileIncluded('modules/good-bunny.php'));
  }
  
  function testIsDirectoryIncludedPastMaxDepth() {
    $coverage = new CodeCoverage();
    $coverage->maxDirectoryDepth = 5;
    $this->assertTrue($coverage->isDirectoryIncluded('aaa', 1));
    $this->assertFalse($coverage->isDirectoryIncluded('aaa', 5));
  }
  
  function testIsDirectoryIncluded() {
    $coverage = new CodeCoverage();
    $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
    $coverage->excludes = array('b$');
    $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
    $coverage->includes = array('a$'); // includes are ignore, all dirs are included unless excluded
    $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
    $coverage->excludes = array('.*a$');
    $this->assertFalse($coverage->isDirectoryIncluded('aaa', 0));
  }  
  
  function testFilter() {
    $coverage = new CodeCoverage();
    $data = array('a' => 0, 'b' => 0, 'c' => 0);
    $coverage->includes = array('b');
    $coverage->filter($data);
    $this->assertEqual(array('b' => 0), $data);
  }
  
  function testUntouchedFiles() {
    $coverage = new CodeCoverage();
    $touched = array_flip(array("test/coverage_test.php"));
    $actual = array();
    $coverage->includes = array('coverage_test\.php$');
    $parentDir = realpath(dirname(__FILE__));
    $coverage->getUntouchedFiles($actual, $touched, $parentDir, $parentDir);
    $this->assertEqual(array("coverage_test.php"), $actual);
  }
  
  function testResetLog() {
    $coverage = new CodeCoverage();
    $coverage->log = tempnam(NULL, 'php.xdebug.coverage.test.');
    $coverage->resetLog();
    $this->assertTrue(file_exists($coverage->log));
  }
  
  function testSettingsSerialization() {
    $coverage = new CodeCoverage();
    $coverage->log = '/banana/boat';
    $coverage->includes = array('apple', 'orange');
    $coverage->excludes = array('tomato', 'pea');
    $data = $coverage->getSettings();
    $this->assertNotNull($data);
    
    $actual = new CodeCoverage();
    $actual->setSettings($data);
    $this->assertEqual('/banana/boat', $actual->log);
    $this->assertEqual(array('apple', 'orange'), $actual->includes);
    $this->assertEqual(array('tomato', 'pea'), $actual->excludes);
  } 
  
  function testSettingsCanBeReadWrittenToDisk() {
    $coverage = new CodeCoverage();
    $coverage->log = '/banana/boat';
    $coverage->writeSettings();
    
    $actual = new CodeCoverage();
    $actual->readSettings();
    $this->assertEqual('/banana/boat', $actual->log);
  }
}

class CoverageDataHandlerTest extends UnitTestCase {
  
  function testAggregateCoverageCode() {
    $handler = new CoverageDataHandler($this->tempdb());
    $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, -2));
    $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, 10));
    $this->assertEqual(-2, $handler->aggregateCoverageCode(10, -2));
    $this->assertEqual(-1, $handler->aggregateCoverageCode(-1, -1));
    $this->assertEqual(10, $handler->aggregateCoverageCode(-1, 10));
    $this->assertEqual(10, $handler->aggregateCoverageCode(10, -1));
    $this->assertEqual(20, $handler->aggregateCoverageCode(10, 10));
  }
  
  function testSimpleWriteRead() {
    $handler = new CoverageDataHandler($this->tempdb());
    $handler->createSchema();
    $coverage = array(10 => -2, 20 => -1, 30 => 0, 40 => 1);
    $handler->write(array('file' => $coverage));
    
    $actual = $handler->readFile('file');
    $expected = array(10 => -2, 20 => -1, 30 => 0, 40 => 1);
    $this->assertEqual($expected, $actual);
  }
  
  function testMultiFileWriteRead() {    
    $handler = new CoverageDataHandler($this->tempdb());
    $handler->createSchema();
    $handler->write(array(
    	'file1' => array(-2, -1, 1), 
    	'file2' => array(-2, -1, 1)
    ));
    $handler->write(array(
    	'file1' => array(-2, -1, 1)
    ));
    
    $expected = array(
    	'file1' => array(-2, -1, 2),
    	'file2' => array(-2, -1, 1)
    );
    $actual = $handler->read();
    $this->assertEqual($expected, $actual);
  }
  
  function testGetfilenames() {
    $handler = new CoverageDataHandler($this->tempdb());
    $handler->createSchema();
    $rawCoverage = array('file0' => array(), 'file1' => array());
    $handler->write($rawCoverage);
    $actual = $handler->getFilenames();
    $this->assertEqual(array('file0', 'file1'), $actual);
  }
  
  function testWriteUntouchedFiles() {
    $handler = new CoverageDataHandler($this->tempdb());
    $handler->createSchema();
    $handler->writeUntouchedFile('bluejay');
    $handler->writeUntouchedFile('robin');
    $this->assertEqual(array('bluejay', 'robin'), $handler->readUntouchedFiles());
  }
  
  function testLtrim() {
    $this->assertEqual('ber', CoverageDataHandler::ltrim('goo', 'goober'));
    $this->assertEqual('some/file', CoverageDataHandler::ltrim('./', './some/file'));
    $this->assertEqual('/x/y/z/a/b/c', CoverageDataHandler::ltrim('/a/b/', '/x/y/z/a/b/c'));
  }

  function tempdb() {
    return tempnam(NULL, 'coverage.test.db');
  }
}
