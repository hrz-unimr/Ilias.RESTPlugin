ILIAS REST Plugin
=====================
This is an extensible REST interface that allows developers to create RESTful APIs on top of the [ILIAS LMS](http://www.ilias.de).

![Alt text](https://cloud.githubusercontent.com/assets/7113474/3655327/eeec4264-1177-11e4-8256-869db1f98c29.PNG "ILIAS REST Plugin")

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
<pre><code>curl -X GET http://localhost/restplugin.php/routes
</code></pre>

Example 2
**Retrieve an OAuth 2.0 Access Token**
<pre><code>curl -X POST http://localhost/restplugin.php/v1/oauth2/token -d "grant_type=password&username=root&password=homer"| python -m json.tool
</code></pre>
<pre><code>curl -H "Authorization: Bearer <ACCESS_TOKEN>" http://...
</code></pre>

##Installation##
Current release: v.0.5.6-alpha
* mkdir -p ILIAS_DIR/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* cd ILIAS_DIR/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* wget https://github.com/eqsoft/RESTPlugin/archive/v.0.5.6-alpha.zip
* unzip v.0.5.6.zip
* mv RESTPlugin-v.0.5.6-alpha Rest
* Copy Rest/gateways/restplugin.php to /
* Plugin directories must be readable by www-user
* Activate Plugin in Administration > Plugins

