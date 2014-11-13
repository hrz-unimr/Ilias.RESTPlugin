Mobile v1 API for the ILIAS REST Plugin
======================================
This is an extension under development for the [ILIAS REST Plugin](https://github.com/hrz-unimr/RESTPlugin).
The goal is to provide additional REST endpoints for a mobile app.

#### Features:
* tba

#### Parameters:
* todo

Examples
---------
Example
> **Retrieve info about an object with ref_id 67 **
curl -X GET http://localhost/restplugin.php/v1/describr/67

> ** Retrieve object properties with an obj_id **
curl -X GET http://localhost/restplugin.php/v1/describr/308?id_type=obj_id

#### History:
v0.1 - 2014-07

##### Authors:
Dirk Schaefer <schaefer at hrz.uni-marburg.de>