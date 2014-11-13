Files v1 API for the ILIAS REST Plugin
======================================
This is an extension for the [ILIAS REST Plugin](https://github.com/hrz-unimr/RESTPlugin)
providing additional REST endpoints for file handling.

#### Features:
* File Download via ref_id
* File Upload

#### TODO:
Check if requesting user is admin or owner of a file.
Or in case of file upload, if the user has the permission to upload to the repository location.

Examples
---------
Example
> **Retrieve a file with ref_id 67 **
curl -X GET http://localhost/restplugin.php/v1/files/67 > x.pdf

> ** Add a file to repository object with ref_id 65 (e.g. a course) **
curl --form uploadfile=@image.jpg --form ref_id=65 http://localhost/restplugin.php/v1/files
