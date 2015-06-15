ILIAS REST Plugin
=====================
This is an extensible REST interface that allows developers to create RESTful APIs on top of the [ILIAS LMS](http://www.ilias.de).

![Alt text](https://cloud.githubusercontent.com/assets/7113474/4717608/c75ea6c4-5916-11e4-9337-a4cdc869224a.PNG "ILIAS REST Plugin")

#### Features:
* Permission management for resources depending on REST clients using API-Keys
* Full OAuth 2.0 support (see [RFC6749](http://tools.ietf.org/html/rfc6749)) including the grant types:
    * Authorization Code
    * Implicit
    * Resource Owner Password Credentials
    * Client Credentials
* CRUD (Create-Read-Update-Delete) principle for resources
* Easy integration of new REST endpoints possible
* Based on the PHP SLIM Framework

Note: Please refer to the Wiki pages for further information.

#### Example 
**Retrieve available routes**

    curl -X GET http://localhost/restplugin.php/routes
    


