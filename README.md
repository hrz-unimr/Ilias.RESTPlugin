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

* Create the required folder structure inside your ILIAS installation:  
  **mkdir** -p *${ILIAS_DIR}*/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* Open the newly created directory:  
  **cd** *${ILIAS_DIR}*/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
* Download latest release version of the RESTPlugin to this directory:  
  **wget** https://github.com/hrz-unimr/Ilias.RESTPlugin/archive/v.0.7.2-alpha.zip
* Simply unzip the downloaded zip-file to its current location:  
  **unzip** v.0.7.2.zip
* Rename the extracted folder, such that it can be loaded by ILIAS:  
  **mv** RESTPlugin-v.0.7.2-alpha REST
* Copy the actual interface for the RESTController into your ILIAS base folder:  
  **cp** REST/gateways/restplugin.php *${ILIAS_DIR}*/
* Create the *${ILIAS_LOG_DIR}*/restplugin.log file
* Open your *${ILIAS_DIR}*/ilias.ini.php file and append:
````
    [rest]
    path = "./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST"
````
* You can now update your database and activate the plugin under Administration > Plugins from inside of ILIAS
  
###Notes###
* The variables *${ILIAS_DIR}* and *${ILIAS_LOG_DIR}* should point to your ILIAS installation
  directory (eg. /var/www/ilias) and the external logging directory set during ILIAS installation
  (eg /var/log) respectively.  
  The log directory can also be retrieved by looking at the *path*-variable under the [*log*] section 
  inside your *${ILIAS_DIR}*/ilias.ini.php file.
* On a unix-like operating-systems make sure the plugin directories
  *${ILIAS_DIR}*/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
  is readable and the log-file *${ILIAS_LOG_DIR}*/restplugin.log
  is writeable by the apache process. Normally that means the user/group *www-data* needs read or 
  write access to those directories accordingly.
