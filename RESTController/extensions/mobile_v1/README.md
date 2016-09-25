Mobile v1 REST API
======================================
This is an extension under development for the [ILIAS REST Plugin](https://github.com/hrz-unimr/RESTPlugin).
The goal is to provide additional REST endpoints for a mobile app.

#### Features:
* Mobile Search

#### Parameters:
* todo

#### Notes
Mobile Search: 
For the installation of Elastic Search and the River Connector please refer to:
https://github.com/jprante/elasticsearch-river-jdbc

In our scenario we set up a JDBC-river as follows:

    curl -XPUT 'localhost:9200/_river/my_jdbc_river/_meta' -d '{
        "type" : "jdbc",
        "schedule" : "0 0-59 0-23 ? * *",
        "jdbc" : {
            "url" : "jdbc:mysql://localhost:3306/ilias",
            "user" : "root",
            "password" : "****",
            "sql": "SELECT obj_id as _id, obj_id, type, title, DATEDIFF(NOW(), last_update) AS ageindays FROM object_data WHERE type IN (\"crs\") HAVING ageindays<2*365"
        }
    }'

Examples
---------
Example
> **Retrieve info about an object with ref_id 67 **
curl -K token --data "q=Test" -X POST http://localhost/ilias5/restplugin.php/m/v1/search

#### History:
v0.2 - 2014-12 Mobile Search --
v0.1 - 2014-07 INIT

##### Authors:
Dirk Schaefer <schaefer at hrz.uni-marburg.de>