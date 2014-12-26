#!/bin/bash

echo "*******************"
echo "Test Suite - Vertex"

BASEDIR=$(dirname $0)
PHP="php -d date.timezone='Europe/Paris'"

MODE="$1"

if [[ "testsonly" != $MODE  ]]
then

echo "******** Mess Detector ********" | tee ${BASEDIR}/metrics/phpmd_results.md
$PHP ./vendor/bin/phpmd ${BASEDIR}/../Framework/ text ${BASEDIR}/md_ruleset.xml | tee -a ${BASEDIR}/metrics/phpmd_results.md

echo "******** Copy/Paste Detector ********" | tee ${BASEDIR}/metrics/phpcpd_results.md
$PHP ./vendor/bin/phpcpd ${BASEDIR}/../Framework/ | tee -a ${BASEDIR}/metrics/phpcpd_results.md

echo "******** Depend ********"
$PHP ./vendor/bin/pdepend --summary-xml=${BASEDIR}/metrics/phpdepend_summary.xml --jdepend-chart=${BASEDIR}/metrics/phpdepend_depend.svg --overview-pyramid=${BASEDIR}/metrics/phpdepend_pyramid.svg Framework/ > ${BASEDIR}/metrics/phpdepend_results.md

echo "******** CodeSniffer ********"
$PHP ./vendor/bin/phpcs Framework

fi

echo "******** Units Tests ********" | tee ${BASEDIR}/metrics/tests_results.md
$PHP ./vendor/bin/phpunit -c ${BASEDIR}/../ --bootstrap ${BASEDIR}/bootstrap.php | tee -a ${BASEDIR}/metrics/tests_results.md

echo "********"
echo "Done."

exit 0;