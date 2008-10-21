#!/bin/sh
SIMPLETEST_PHP_OPTS="-d include_path=.:/home/dhubler/development/smarty/2.6.19/Smarty-2.6.19/libs:/usr/share/php"
SIMPLETEST_DIR=`dirname $0`/..
php ${SIMPLETEST_PHP_OPTS} ${SIMPLETEST_DIR}/test/simpletest_coverage.php open
php ${SIMPLETEST_PHP_OPTS} -d auto_prepend_file=${SIMPLETEST_DIR}/autocoverage.php ${SIMPLETEST_DIR}/test/unit_tests.php
php ${SIMPLETEST_PHP_OPTS} ${SIMPLETEST_DIR}/test/simpletest_coverage.php close
php ${SIMPLETEST_PHP_OPTS} ${SIMPLETEST_DIR}/test/simpletest_coverage.php report
