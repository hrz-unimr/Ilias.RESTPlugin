Codeception
- based on PHPUnit
- supports testing of web services
- url: http://codeception.com/

Installation:
wget http://codeception.com/codecept.phar
php codecept.phar bootstrap
apt-get install php5-curl

Note: In order to test the REST endpoints, you have to edit tests/api.suite.yml and adapt the urls.

A test run can be done with
php codecept.phar run api

Further information on running or creating test cases can be found via
php codecept.phar help run
http://codeception.com/docs/10-WebServices
