ILIAS REST Plugin
=====================
This is an extensible REST interface that allows developers to create RESTful APIs on top of the [ILIAS LMS](http://www.ilias.de).

![Alt text](https://cloud.githubusercontent.com/assets/7113474/4717608/c75ea6c4-5916-11e4-9337-a4cdc869224a.PNG "ILIAS REST Plugin")

#### Features:
* CRUD (Create-Read-Update-Delete) principle for resources
* Permission management for resources depending on REST clients
* Full OAuth 2.0 support (see [RFC6749](http://tools.ietf.org/html/rfc6749)) including the grant types:
    * Authorization Code
    * Implicit
    * Resource Owner Password Credentials
    * Client Credentials
* Easy integration for new REST endpoints
* API discovery
* Based on the PHP SLIM Framework

Examples
---------
Example 1
**Retrieve available routes**

    curl -X GET http://localhost/restplugin.php/routes

Example 2
**Retrieve an OAuth 2.0 Access Token**

    curl -X POST http://localhost/restplugin.php/v1/oauth2/token -d "grant_type=password&username=root&password=homer"| python -m json.tool


    curl -H "Authorization: Bearer <ACCESS_TOKEN>" http://...

##Installation##
Current release: v.0.7.2-alpha
* mkdir -p ILIAS_DIR/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* cd ILIAS_DIR/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* wget https://github.com/hrz-unimr/Ilias.RESTPlugin/archive/v.0.7.2-alpha.zip
* unzip v.0.7.2.zip
* mv RESTPlugin-v.0.7.2-alpha REST
* Copy REST/gateways/restplugin.php to /
* Plugin directories must be readable by apache process (www-data)
* Create www-data writable logfile /var/log/restplugin.log
* Activate Plugin in Administration > Plugins

