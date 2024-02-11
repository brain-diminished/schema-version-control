#!/usr/bin/env bash

# Use this file to start a MySQL8 database using Docker and then run the test suite on the MySQL8 database.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR" || exit
cd ..

docker run --rm --name schema_manager_test_case -p 3306:3306 -p 33060:33060 -e MYSQL_ROOT_PASSWORD=password -d mysql:8 mysqld --default-authentication-plugin=mysql_native_password

# Let's wait for MySQL 8 to start
sleep 20

vendor/bin/phpunit -c phpunit.xml.dist "$NO_COVERAGE"
RESULT_CODE=$?

docker stop schema_manager_test_case

exit $RESULT_CODE
