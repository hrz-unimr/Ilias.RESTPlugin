## Administration Routes
---
Workspace Administration of the ILIAS REST Plugin
======================================
This is an extension for the [ILIAS REST Plugin](https://github.com/hrz-unimr/RESTPlugin)

The purpose of the endpoints defined by this extension is to enable administration of
the user workspaces.

### Features:
* Creates an overview about
 - how many users the system has
 - how many of them have content in their workspaces
 - how many of them have shared items
* Creates another overview about
 - a list of user_ids and the number of items their ws contains
 - sorted by number of items
* Can list the items of type "file" given a user_id

### API

### Examples
---
describe API for the ILIAS REST Plugin
======================================
This is an extension for the [ILIAS REST Plugin](https://github.com/hrz-unimr/RESTPlugin)
providing additional REST endpoints for explaining ILIAS objects.

This can be seen as a tool for administrators and developers to get descriptions of ilias objects / users in a quick way.

#### Features:
* Provides information about ILIAS objects given a ref_id or obj_id

#### Parameters:
If not further specified the ref_id is expected as resource.
Otherwise an additional query parameter must be provided.

Examples
---------
Example
> **Retrieve info about an object with ref_id 67 **
curl -X GET http://localhost/restplugin.php/admin/describe/67

> ** Retrieve object properties with an obj_id **
curl -X GET http://localhost/restplugin.php/admin//describe/308?id_type=obj_id
