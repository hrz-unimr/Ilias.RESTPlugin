Codeception
- based on PHPUnit
- supports testing of web services
- url: http://codeception.com/

Installation:
- wget http://codeception.com/codecept.phar
- php codecept.phar bootstrap
- apt-get install php5-curl

Notes: In order to test the REST endpoints, you have to edit tests/api.suite.yml and adapt the urls.
Furthermore it is assumed that you test with user "root:homer". Otherwise the file commons/TestCommons.php
has to be adapted accordingly.

A test run that involves all defined API tests can be done with 

`php codecept.phar run api`

A test run with a particular subset can be done via path notation, e.g.

`php codecept.phar run tests/api/core`


If you interested in other kinds of tests but need a certain scenario/setup, e.g.
a bunch of system users, some courses and tests, you might want to use one of the predefined scenarios:

`php codecept.phar --debug run tests/api/scenarios/peristerion/PeristerionUpCest.php`
(Here the infix "Up" denotes the construction of a scenario and "Down" the destruction.)


Further information on running or creating API test cases can be found via console
`php codecept.phar help run`
or at the codeception website http://codeception.com/docs/10-WebServices

