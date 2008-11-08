#!/usr/bin/php 
<?php
# 
# Initialize code coverage data collection, next step is to run your tests
# with ini setting auto_prepend_file=autocoverage.php ...
#
# Example: 
#  php-coverage-open.php --include='.*\.php$' --include='.*\.inc$' --exclude='.*/tests/.*'

require_once(dirname(__FILE__) . '/../coverage_utils.php');
CoverageUtils::requireSqlite();
require_once(dirname(__FILE__) . '/../coverage.php');
$cc = CodeCoverage::getMainInstance();
$cc->log = 'coverage.sqlite';
$args = CoverageUtils::parseArguments($_SERVER['argv'], TRUE);
$cc->includes = CoverageUtils::issetOr($args['include[]'], array('.*\.php$'));
$cc->excludes = CoverageUtils::issetOr($args['exclude[]']); 
$cc->maxDirectoryDepth = (int)CoverageUtils::issetOr($args['maxdepth'], '1');
$cc->resetLog();
$cc->writeSettings();
