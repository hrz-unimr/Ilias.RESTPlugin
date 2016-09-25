ILIAS REST Plugin
=====================
This is a plugin for the [ILIAS Learning Management System](http://www.ilias.de), which provides a customizable REST API.

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
* Tools included (IShell, System Client, API Testing, IScenarios)

Note: Please refer to the wiki pages for further information.

#### Example
**Retrieve all available routes**

    curl -X GET http://api.my-ilias.de/v2/routes
