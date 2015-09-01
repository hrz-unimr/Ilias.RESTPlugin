ILIAS REST Plugin
=====================
This is an extensible REST interface that allows developers to create RESTful APIs on top of the [ILIAS LMS](http://www.ilias.de).
<p align="center">
<img src="https://cloud.githubusercontent.com/assets/7113474/9606244/912b1336-50c2-11e5-88c1-bf130bd9420f.png" alt="ILIAS REST Plugin" />
</p>

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

Note: Please refer to the wiki pages for further information.

#### Example 
**Retrieve available routes**

    curl -X GET http://localhost/restplugin.php/routes
    


